import fs from 'fs';
import fs_extra from 'fs-extra';
import { join } from 'path';
import unzip from 'yauzl';

const { removeSync, ensureDirSync } = fs_extra;

const isBuilding = Boolean(process.env.NATIVEPHP_BUILDING);
const phpBinaryPath = process.env.NATIVEPHP_PHP_BINARY_PATH;
const phpVersion = process.env.NATIVEPHP_PHP_BINARY_VERSION;

const isArm64 = isBuilding ? process.argv.includes('--arm64') : process.arch.includes('arm64');
const isWindows = isBuilding ? process.argv.includes('--win') : process.platform.includes('win32');
const isLinux = isBuilding ? process.argv.includes('--linux') : process.platform.includes('linux');
const isDarwin = isBuilding ? process.argv.includes('--mac') : process.platform.includes('darwin');

const platform = {
    os: false,
    arch: false,
    phpBinary: 'php',
};

if (isWindows) {
    platform.os = 'win';
    platform.arch = 'x64';
    platform.phpBinary += '.exe';
}

if (isLinux) {
    platform.os = 'linux';
    platform.arch = 'x64';
}

if (isDarwin) {
    platform.os = 'mac';
    platform.arch = 'x64';
}

if (isArm64) {
    platform.arch = 'arm64';
}

if (isBuilding) {
    platform.arch = process.argv.includes('--x64') ? 'x64' : platform.arch;
    platform.arch = process.argv.includes('--arm64') ? 'arm64' : platform.arch;
}

const phpVersionZip = 'php-' + phpVersion + '.zip';
const binarySrcDir = join(phpBinaryPath, platform.os, platform.arch, phpVersionZip);
const binaryDestDir = join(process.env.NATIVEPHP_BUILD_PATH, 'php');

function extractZip(source, destination, binaryName) {
    return new Promise((resolve, reject) => {
        unzip.open(source, { lazyEntries: true }, (err, zipfile) => {
            if (err) {
                reject(err);

                return;
            }

            let binaryPath = join(destination, binaryName);

            zipfile.on('entry', (entry) => {
                zipfile.openReadStream(entry, (entryErr, readStream) => {
                    if (entryErr) {
                        reject(entryErr);

                        return;
                    }

                    binaryPath = join(destination, binaryName);
                    const writeStream = fs.createWriteStream(binaryPath);

                    readStream.pipe(writeStream);

                    writeStream.on('close', () => {
                        zipfile.readEntry();
                    });

                    writeStream.on('error', reject);
                });
            });

            zipfile.on('end', () => resolve(binaryPath));
            zipfile.on('error', reject);
            zipfile.readEntry();
        });
    });
}

async function main() {
    console.log('Binary Source: ', binarySrcDir);
    console.log('Binary Filename: ', platform.phpBinary);
    console.log('PHP version: ' + phpVersion);

    if (! platform.phpBinary) {
        return;
    }

    console.log('Unzipping PHP binary from ' + binarySrcDir + ' to ' + binaryDestDir);
    removeSync(binaryDestDir);
    ensureDirSync(binaryDestDir);

    const binaryPath = await extractZip(binarySrcDir, binaryDestDir, platform.phpBinary);

    fs.chmodSync(binaryPath, 0o755);
    console.log('Copied PHP binary to ', binaryPath);
}

main()
    .then(() => process.exit(0))
    .catch((error) => {
        console.error('Error copying PHP binary', error);
        process.exit(1);
    });
