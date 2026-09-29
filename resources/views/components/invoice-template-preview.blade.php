@props(['slug'])

@php
    $previews = [
        'classic-gst' => [
            'label' => __('Classic'),
            'class' => 'tpl-classic',
        ],
        'minimal-gst' => [
            'label' => __('Minimal'),
            'class' => 'tpl-minimal',
        ],
        'modern-gst' => [
            'label' => __('Modern'),
            'class' => 'tpl-modern',
        ],
        'classic-bill' => [
            'label' => __('Classic'),
            'class' => 'tpl-classic',
        ],
        'minimal-bill' => [
            'label' => __('Minimal'),
            'class' => 'tpl-minimal',
        ],
    ];

    $preview = $previews[$slug] ?? $previews['classic-gst'];
@endphp

<div {{ $attributes->merge(['class' => 'tpl-preview '.$preview['class']]) }} aria-hidden="true">
    <div class="tpl-preview-sheet">
        <div class="tpl-preview-header"></div>
        <div class="tpl-preview-meta"></div>
        <div class="tpl-preview-parties">
            <span></span>
            <span></span>
        </div>
        <div class="tpl-preview-table">
            <span></span>
            <span></span>
            <span></span>
        </div>
        <div class="tpl-preview-footer"></div>
    </div>
    <span class="tpl-preview-badge">{{ $preview['label'] }}</span>
</div>

<style>
    .tpl-preview {
        position: relative;
        overflow: hidden;
        border-radius: 0.75rem;
        border: 1px solid rgb(226 232 240);
        background: linear-gradient(180deg, rgb(248 250 252), rgb(241 245 249));
        height: 7.5rem;
        max-height: 7.5rem;
    }

    .tpl-preview-sheet {
        position: absolute;
        inset: 8% 7% 12%;
        border-radius: 4px;
        background: #fff;
        box-shadow: 0 1px 3px rgb(15 23 42 / 0.08);
        padding: 6%;
        display: flex;
        flex-direction: column;
        gap: 5%;
    }

    .tpl-preview-header,
    .tpl-preview-meta,
    .tpl-preview-footer {
        border-radius: 2px;
        background: rgb(226 232 240);
    }

    .tpl-preview-header { height: 18%; }
    .tpl-preview-meta { height: 8%; }
    .tpl-preview-footer { height: 10%; margin-top: auto; }

    .tpl-preview-parties {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 4%;
        height: 14%;
    }

    .tpl-preview-parties span {
        border-radius: 2px;
        background: rgb(241 245 249);
        border: 1px solid rgb(226 232 240);
    }

    .tpl-preview-table {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 5%;
        padding-top: 2%;
    }

    .tpl-preview-table span {
        height: 12%;
        border-radius: 2px;
        background: rgb(248 250 252);
        border-bottom: 1px solid rgb(226 232 240);
    }

    .tpl-preview-badge {
        position: absolute;
        left: 0.5rem;
        bottom: 0.4rem;
        border-radius: 9999px;
        background: rgb(255 255 255 / 0.92);
        padding: 0.1rem 0.45rem;
        font-size: 0.6rem;
        font-weight: 600;
        color: rgb(71 85 105);
        box-shadow: 0 1px 2px rgb(15 23 42 / 0.06);
    }

    /* Classic — bordered grid */
    .tpl-classic .tpl-preview-sheet {
        border: 1px solid rgb(148 163 184);
        padding: 0;
        gap: 0;
    }

    .tpl-classic .tpl-preview-header {
        height: 22%;
        margin: 0;
        border-radius: 0;
        border-bottom: 1px solid rgb(148 163 184);
        background: rgb(248 250 252);
    }

    .tpl-classic .tpl-preview-meta { display: none; }

    .tpl-classic .tpl-preview-parties {
        height: 16%;
        margin: 0;
        gap: 0;
        border-bottom: 1px solid rgb(148 163 184);
    }

    .tpl-classic .tpl-preview-parties span {
        border-radius: 0;
        border: 0;
        border-right: 1px solid rgb(148 163 184);
        background: #fff;
    }

    .tpl-classic .tpl-preview-parties span:last-child { border-right: 0; }

    .tpl-classic .tpl-preview-table {
        padding: 0;
        gap: 0;
        border-bottom: 1px solid rgb(148 163 184);
    }

    .tpl-classic .tpl-preview-table span {
        height: 14%;
        border-radius: 0;
        border-bottom: 1px solid rgb(203 213 225);
        background: #fff;
    }

    .tpl-classic .tpl-preview-table span:first-child {
        background: rgb(241 245 249);
        border-bottom: 1px solid rgb(148 163 184);
    }

    .tpl-classic .tpl-preview-footer {
        height: 14%;
        margin: 0;
        border-radius: 0;
        background: #fff;
    }

    /* Minimal — open, line-based */
    .tpl-minimal .tpl-preview-header {
        height: 16%;
        background: transparent;
        border-bottom: 2px solid rgb(51 65 85);
    }

    .tpl-minimal .tpl-preview-meta {
        height: 6%;
        background: transparent;
        border-bottom: 1px dashed rgb(203 213 225);
    }

    .tpl-minimal .tpl-preview-parties span {
        background: transparent;
        border: 0;
        border-bottom: 1px solid rgb(226 232 240);
    }

    .tpl-minimal .tpl-preview-table span {
        background: transparent;
        border-bottom: 1px solid rgb(226 232 240);
    }

    .tpl-minimal .tpl-preview-table span:first-child {
        border-bottom: 2px solid rgb(51 65 85);
        background: transparent;
    }

    .tpl-minimal .tpl-preview-footer {
        background: transparent;
        border-top: 1px solid rgb(226 232 240);
    }

    /* Modern — accent banner */
    .tpl-modern .tpl-preview-sheet { padding: 0; gap: 0; overflow: hidden; }

    .tpl-modern .tpl-preview-header {
        height: 24%;
        margin: 0;
        border-radius: 0;
        background: linear-gradient(135deg, #312e81, #6366f1);
    }

    .tpl-modern .tpl-preview-meta {
        height: 10%;
        margin: 6%;
        width: calc(100% - 12%);
        background: rgb(241 245 249);
        border: 1px solid rgb(226 232 240);
        border-radius: 4px;
    }

    .tpl-modern .tpl-preview-parties {
        margin: 0 6%;
        height: 12%;
        gap: 6%;
    }

    .tpl-modern .tpl-preview-parties span {
        background: rgb(248 250 252);
        border: 1px solid rgb(226 232 240);
        border-radius: 4px;
    }

    .tpl-modern .tpl-preview-table {
        margin: 4% 6% 0;
        gap: 0;
        border: 1px solid rgb(199 210 254);
        border-radius: 4px;
        overflow: hidden;
    }

    .tpl-modern .tpl-preview-table span:first-child {
        background: #4338ca;
        border-bottom: 1px solid #4f46e5;
    }

    .tpl-modern .tpl-preview-table span {
        border-bottom: 1px solid rgb(226 232 240);
        background: #fff;
    }

    .tpl-modern .tpl-preview-footer {
        margin: 6%;
        height: 8%;
        background: rgb(248 250 252);
        border-top: 1px solid rgb(226 232 240);
        border-radius: 0;
    }
</style>
