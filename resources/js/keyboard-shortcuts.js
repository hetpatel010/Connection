const isMac = /Mac|iPhone|iPad|iPod/.test(navigator.platform);

document.documentElement.classList.add(isMac ? 'platform-mac' : 'platform-win');

function modifierPressed(event) {
    return isMac ? event.metaKey : event.ctrlKey;
}

function isTypingInField(element) {
    if (! element) {
        return false;
    }

    const tag = element.tagName;

    return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || element.isContentEditable;
}

function activeShortcutScope() {
    return document.activeElement?.closest('[data-shortcut-scope]')
        ?? document.querySelector('[data-shortcut-scope]');
}

function triggerSave() {
    const scope = activeShortcutScope();

    const shortcutButton = scope?.querySelector('[data-shortcut-save]')
        ?? document.querySelector('[data-shortcut-save]:not([disabled])');

    if (shortcutButton) {
        shortcutButton.click();

        return;
    }

    const form = document.activeElement?.closest('form[data-shortcut-form]')
        ?? document.querySelector('form[data-shortcut-form]');

    if (form) {
        form.requestSubmit();
    }
}

function triggerPrimaryAction() {
    const scope = activeShortcutScope();

    const primaryButton = scope?.querySelector('[data-shortcut-primary]:not([disabled])')
        ?? document.querySelector('[data-shortcut-primary]:not([disabled])');

    if (primaryButton) {
        primaryButton.click();
    }
}

function openGlobalSearch() {
    window.dispatchEvent(new CustomEvent('open-global-search'));
}

function focusListSearch() {
    const searchInput = document.querySelector('[data-shortcut-search]');

    if (searchInput) {
        searchInput.focus();
        searchInput.select?.();

        return true;
    }

    return false;
}

document.addEventListener('keydown', (event) => {
    if (modifierPressed(event) && event.key.toLowerCase() === 's') {
        event.preventDefault();
        triggerSave();

        return;
    }

    if (modifierPressed(event) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        openGlobalSearch();

        return;
    }

    if (event.key === '/' && ! isTypingInField(document.activeElement) && ! modifierPressed(event) && ! event.altKey) {
        if (focusListSearch()) {
            event.preventDefault();
        }

        return;
    }

    if (modifierPressed(event) && event.key === 'Enter' && ! event.shiftKey) {
        if (document.activeElement?.tagName === 'TEXTAREA') {
            return;
        }

        const scope = activeShortcutScope();

        if (scope?.querySelector('[data-shortcut-primary]')) {
            event.preventDefault();
            triggerPrimaryAction();
        }

        return;
    }

    if (event.key === '?' && ! isTypingInField(document.activeElement) && ! modifierPressed(event)) {
        event.preventDefault();
        window.dispatchEvent(new CustomEvent('open-shortcuts-help'));
    }

    if (event.key === 'Escape') {
        window.dispatchEvent(new CustomEvent('close-shortcuts-help'));
    }
});
