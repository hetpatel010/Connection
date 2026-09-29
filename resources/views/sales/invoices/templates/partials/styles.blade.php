<style>
    :root {
        --inv-ink: #1a1a1a;
        --inv-muted: #555;
        --inv-line: #bbb;
        --inv-bg: #f5f5f5;
        --inv-accent: #4338ca;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
        font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
        background: #e8ecf0;
        color: var(--inv-ink);
        padding: 16px;
        font-size: 11px;
        line-height: 1.4;
    }

    .toolbar { margin: 0 auto 12px; max-width: 210mm; display: flex; justify-content: space-between; }
    .toolbar button { background: var(--inv-accent); color: #fff; border: 0; border-radius: 4px; padding: 6px 14px; cursor: pointer; font-size: 12px; }
    .toolbar a { color: var(--inv-accent); text-decoration: none; font-size: 12px; }

    .doc {
        max-width: 210mm;
        margin: 0 auto;
        background: #fff;
        border: 1px solid #888;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    }

    /* ── Single master table ── */
    .invoice-sheet {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .invoice-sheet th,
    .invoice-sheet td {
        border: 1px solid var(--inv-line);
        padding: 6px 8px;
        vertical-align: top;
        color: var(--inv-ink);
    }

    .invoice-sheet .items-head th,
    .invoice-sheet .subhead th {
        background: var(--inv-bg);
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        text-align: center;
        padding: 5px 4px;
        border: 1px solid var(--inv-line);
        vertical-align: middle;
    }

    .invoice-sheet .subhead th {
        font-size: 8px;
        font-weight: 600;
        background: #eee;
        padding: 3px 4px;
        border-top: 0;
    }

    .invoice-sheet .cell-tight { padding: 4px 6px; }

    .invoice-sheet .cell-none { padding: 0; }

    .invoice-sheet .cell-bg { background: var(--inv-bg); }

    .seller-block { display: flex; gap: 10px; align-items: flex-start; }

    .branding-logo { max-height: 48px; max-width: 80px; object-fit: contain; flex-shrink: 0; }

    .seller-name { font-size: 14px; font-weight: 700; line-height: 1.3; margin-bottom: 3px; }

    .seller-meta { font-size: 10px; color: var(--inv-muted); white-space: pre-line; line-height: 1.45; margin-bottom: 2px; }

    .seller-line { font-size: 10px; color: var(--inv-muted); line-height: 1.5; }
    .seller-line strong { color: var(--inv-ink); font-weight: 600; }

    .invoice-title {
        text-align: center;
        font-size: 13px;
        font-weight: 800;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        padding: 6px;
        margin-bottom: 0;
        border-bottom: 1px solid var(--inv-line);
        background: var(--inv-bg);
    }

    .meta-list { width: 100%; }

    .meta-row {
        display: flex;
        border-top: 1px solid var(--inv-line);
        font-size: 10px;
        min-height: 22px;
        align-items: center;
    }

    .meta-label {
        width: 46%;
        flex-shrink: 0;
        padding: 4px 8px;
        font-weight: 600;
        color: var(--inv-muted);
        background: var(--inv-bg);
    }

    .meta-value {
        flex: 1;
        padding: 4px 8px;
        font-weight: 600;
    }

    .section-label {
        font-size: 9px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--inv-muted);
        margin-bottom: 4px;
    }

    .party-name { font-size: 12px; font-weight: 700; margin-bottom: 2px; }

    .party-address { font-size: 10px; color: var(--inv-muted); white-space: pre-line; line-height: 1.45; margin-bottom: 2px; }

    .party-detail { font-size: 10px; color: var(--inv-muted); line-height: 1.45; }
    .party-detail strong { color: var(--inv-ink); font-weight: 600; }
    .party-detail span { color: var(--inv-muted); }

    .invoice-sheet a { color: inherit; text-decoration: none; }

    /* Line items */
    .invoice-sheet tr.data-row td { font-size: 10px; padding: 5px 6px; vertical-align: middle; }
    .invoice-sheet tr.data-row:nth-child(even) td { background: #fafafa; }

    .desc { text-align: left; word-wrap: break-word; }
    .right { text-align: right; }
    .center { text-align: center; }
    .mono { font-family: Consolas, "Courier New", monospace; font-size: 10px; }
    .nowrap { white-space: nowrap; }
    .muted { color: var(--inv-muted); }

    /* Summary rows inside sheet */
    .summary-label {
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--inv-muted);
        padding: 4px 8px;
        background: var(--inv-bg);
        border-bottom: 1px solid var(--inv-line);
    }

    .hsn-inner { width: 100%; border-collapse: collapse; }
    .hsn-inner th, .hsn-inner td { border: 0; border-bottom: 1px solid #ddd; padding: 4px 6px; font-size: 9px; }
    .hsn-inner th { background: transparent; font-weight: 700; text-transform: uppercase; font-size: 8px; text-align: left; }
    .hsn-inner tr:last-child td { border-bottom: 0; font-weight: 700; background: var(--inv-bg); }

    .amount-words-block {
        padding: 8px;
        font-size: 10px;
        line-height: 1.5;
        border-top: 1px solid var(--inv-line);
    }

    .amount-words-block .words-label {
        display: block;
        font-size: 8px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--inv-muted);
        margin-bottom: 3px;
    }

    .totals-table { width: 100%; border-collapse: collapse; }
    .totals-table td { border: 0; border-bottom: 1px solid #ddd; padding: 5px 8px; font-size: 10px; }
    .totals-table tr:last-child td { border-bottom: 0; }
    .totals-table .totals-grand td {
        font-size: 12px;
        font-weight: 800;
        border-top: 1px solid var(--inv-ink);
        padding-top: 8px;
        background: var(--inv-bg);
    }

    .notes-line { padding: 6px 8px; font-size: 10px; border-top: 1px solid var(--inv-line); }

    /* Footer row */
    .footer-block { font-size: 10px; line-height: 1.45; margin-bottom: 6px; }
    .footer-text { color: var(--inv-muted); white-space: pre-line; margin-top: 3px; }

    .signature-box { text-align: center; padding-top: 8px; }
    .signature-line { border-top: 1px solid var(--inv-ink); height: 40px; margin-bottom: 4px; }
    .signature-label { font-size: 9px; color: var(--inv-muted); line-height: 1.35; }

    .row-footer-note td {
        text-align: center;
        font-size: 9px;
        color: var(--inv-muted);
        background: var(--inv-bg);
        padding: 6px 8px;
    }

    /* Non-GST */
    .doc-title {
        text-align: center;
        font-size: 14px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        padding: 10px;
        border-bottom: 1px solid var(--inv-line);
        background: var(--inv-bg);
    }

    /* ── Minimal GST layout ── */
    .gst-minimal {
        padding: 14px 16px 10px;
        font-size: 10px;
        line-height: 1.45;
    }

    .min-header {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        padding-bottom: 12px;
        border-bottom: 2px solid var(--inv-ink);
    }

    .min-header-left { display: flex; gap: 10px; align-items: flex-start; flex: 1; min-width: 0; }
    .min-logo { max-height: 42px; max-width: 72px; object-fit: contain; }
    .min-company { font-size: 15px; font-weight: 700; line-height: 1.25; margin-bottom: 2px; }
    .min-address { color: var(--inv-muted); white-space: pre-line; font-size: 9px; margin-bottom: 4px; }
    .min-id-lines { display: flex; flex-wrap: wrap; gap: 8px; font-size: 9px; color: var(--inv-muted); }
    .min-header-right { text-align: right; flex-shrink: 0; }
    .min-doc-label { font-size: 9px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--inv-muted); }
    .min-doc-no { font-size: 13px; font-weight: 700; margin-top: 2px; }
    .min-doc-date { font-size: 10px; color: var(--inv-muted); margin-top: 2px; }

    .min-meta-strip {
        display: flex;
        flex-wrap: wrap;
        gap: 12px 20px;
        padding: 8px 0;
        font-size: 9px;
        color: var(--inv-muted);
        border-bottom: 1px dashed #ccc;
    }

    .min-parties {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px solid #ddd;
    }

    .min-party-label { font-size: 8px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--inv-muted); margin-bottom: 3px; }
    .min-party-name { font-size: 11px; font-weight: 700; margin-bottom: 2px; }
    .min-party-text { font-size: 9px; color: var(--inv-muted); white-space: pre-line; }

    .min-items {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
        font-size: 9px;
    }

    .min-items th {
        text-align: left;
        font-size: 8px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--inv-muted);
        padding: 6px 4px;
        border-bottom: 2px solid var(--inv-ink);
    }

    .min-items td { padding: 5px 4px; border-bottom: 1px solid #e5e5e5; vertical-align: top; }
    .min-items tbody tr:last-child td { border-bottom: 2px solid #ddd; }

    .min-bottom {
        display: grid;
        grid-template-columns: 1.2fr 0.8fr;
        gap: 16px;
        margin-top: 12px;
    }

    .min-section-title { font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--inv-muted); margin-bottom: 4px; }
    .min-hsn { width: 100%; border-collapse: collapse; font-size: 9px; }
    .min-hsn td { padding: 3px 0; border-bottom: 1px dotted #ddd; }
    .min-words { margin-top: 8px; font-size: 9px; line-height: 1.5; }
    .min-words-label { display: block; font-size: 8px; font-weight: 700; text-transform: uppercase; color: var(--inv-muted); margin-bottom: 2px; }
    .min-notes { margin-top: 8px; font-size: 9px; }

    .min-totals { border: 1px solid #ddd; padding: 8px 10px; background: #fafafa; }
    .min-total-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 9px; border-bottom: 1px solid #eee; }
    .min-grand {
        display: flex;
        justify-content: space-between;
        margin-top: 6px;
        padding-top: 6px;
        font-size: 12px;
        font-weight: 800;
        border-top: 2px solid var(--inv-ink);
    }

    .min-footer {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-top: 14px;
        padding-top: 10px;
        border-top: 1px solid #ddd;
        font-size: 9px;
    }

    .min-terms { color: var(--inv-muted); margin-top: 4px; white-space: pre-line; }
    .min-sign { text-align: center; }
    .min-sign-line { border-top: 1px solid var(--inv-ink); height: 36px; margin-bottom: 4px; }
    .min-footnote { text-align: center; font-size: 8px; color: var(--inv-muted); margin-top: 10px; padding-top: 6px; border-top: 1px solid #eee; }

    /* ── Modern GST layout ── */
    .gst-modern { font-size: 10px; line-height: 1.45; }

    .mod-banner {
        background: linear-gradient(135deg, #312e81 0%, #4338ca 55%, #6366f1 100%);
        color: #fff;
        padding: 18px 20px;
    }

    .mod-banner-inner { display: flex; justify-content: space-between; align-items: center; gap: 16px; }
    .mod-brand { display: flex; gap: 12px; align-items: center; min-width: 0; }
    .mod-logo { max-height: 46px; max-width: 76px; object-fit: contain; background: #fff; border-radius: 6px; padding: 4px; }
    .mod-company { font-size: 16px; font-weight: 700; line-height: 1.25; }
    .mod-gstin { font-size: 9px; opacity: 0.9; margin-top: 3px; }
    .mod-title-block { text-align: right; flex-shrink: 0; }
    .mod-title { font-size: 10px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; opacity: 0.85; }
    .mod-number { font-size: 15px; font-weight: 800; margin-top: 2px; }

    .mod-body { padding: 16px 18px 12px; }

    .mod-meta-cards {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 8px;
        margin-bottom: 12px;
    }

    .mod-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 8px 10px;
    }

    .mod-card-label { display: block; font-size: 7px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #64748b; margin-bottom: 2px; }
    .mod-card-value { display: block; font-size: 10px; font-weight: 600; color: #0f172a; }

    .mod-seller-meta { font-size: 9px; color: var(--inv-muted); white-space: pre-line; margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid #e2e8f0; }

    .mod-parties {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-bottom: 14px;
    }

    .mod-party-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 10px 12px;
    }

    .mod-party-label { font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #6366f1; margin-bottom: 4px; }
    .mod-party-name { font-size: 12px; font-weight: 700; margin-bottom: 2px; }
    .mod-party-text { font-size: 9px; color: var(--inv-muted); white-space: pre-line; }

    .mod-items {
        width: 100%;
        border-collapse: collapse;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #c7d2fe;
    }

    .mod-items thead th {
        background: #4338ca;
        color: #fff;
        font-size: 8px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        padding: 7px 5px;
        border: 1px solid #4f46e5;
    }

    .mod-items .mod-subhead th {
        background: #3730a3;
        font-size: 7px;
        padding: 4px 5px;
        border-color: #4f46e5;
    }

    .mod-items td { padding: 6px 5px; border-bottom: 1px solid #e2e8f0; font-size: 9px; vertical-align: middle; }
    .mod-items tbody tr:nth-child(even) td { background: #f8fafc; }
    .mod-line-total { font-weight: 700; }

    .mod-summary {
        display: grid;
        grid-template-columns: 1.3fr 0.7fr;
        gap: 14px;
        margin-top: 14px;
    }

    .mod-section-title { font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #6366f1; margin-bottom: 6px; }

    .mod-hsn { width: 100%; border-collapse: collapse; font-size: 9px; border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden; }
    .mod-hsn th { background: #f1f5f9; text-align: left; padding: 5px 6px; font-size: 8px; font-weight: 700; text-transform: uppercase; }
    .mod-hsn td { padding: 5px 6px; border-top: 1px solid #e2e8f0; }

    .mod-words { margin-top: 10px; padding: 8px 10px; background: #f8fafc; border-radius: 6px; border: 1px solid #e2e8f0; font-size: 9px; }
    .mod-words-label { display: block; font-size: 8px; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 3px; }
    .mod-notes { margin-top: 8px; font-size: 9px; }

    .mod-totals-panel {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 10px 12px;
        align-self: start;
    }

    .mod-total-line { display: flex; justify-content: space-between; padding: 5px 0; font-size: 9px; border-bottom: 1px solid #e2e8f0; color: var(--inv-muted); }
    .mod-total-line span:last-child { color: var(--inv-ink); font-weight: 600; }

    .mod-grand {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 8px;
        padding: 10px 12px;
        background: linear-gradient(135deg, #4338ca, #6366f1);
        color: #fff;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 800;
    }

    .mod-footer {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-top: 16px;
        padding-top: 12px;
        border-top: 1px solid #e2e8f0;
        font-size: 9px;
    }

    .mod-footer-block { margin-bottom: 6px; line-height: 1.45; }
    .mod-sign { text-align: center; }
    .mod-sign-line { border-top: 1px solid var(--inv-ink); height: 40px; margin-bottom: 4px; }
    .mod-footnote { text-align: center; font-size: 8px; color: var(--inv-muted); padding: 8px 18px 10px; background: #f8fafc; border-top: 1px solid #e2e8f0; }

    /* ── Non-GST bill layouts ── */
    .doc-inner { padding: 0; }

    .bill-classic {
        padding: 14px 16px 12px;
        font-size: 10px;
        line-height: 1.45;
    }

    .bill-classic-title {
        text-align: center;
        font-size: 14px;
        font-weight: 800;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        padding: 8px 10px;
        margin-bottom: 12px;
        border: 1px solid var(--inv-line);
        background: var(--inv-bg);
    }

    .bill-classic-top {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        align-items: flex-start;
        padding-bottom: 12px;
        margin-bottom: 12px;
        border-bottom: 1px solid var(--inv-line);
    }

    .bill-meta-box {
        border-collapse: collapse;
        font-size: 10px;
        min-width: 11rem;
    }

    .bill-meta-box td {
        border: 1px solid var(--inv-line);
        padding: 5px 8px;
        vertical-align: middle;
    }

    .bill-meta-box td:first-child {
        background: var(--inv-bg);
        font-weight: 600;
        color: var(--inv-muted);
        width: 42%;
    }

    .bill-classic-parties {
        display: grid;
        grid-template-columns: 1fr 1fr;
        border: 1px solid var(--inv-line);
        margin-bottom: 12px;
    }

    .bill-party-panel {
        padding: 8px 10px;
        border-right: 1px solid var(--inv-line);
    }

    .bill-party-panel:last-child { border-right: 0; }

    .bill-party-label {
        font-size: 8px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--inv-muted);
        margin-bottom: 4px;
    }

    .bill-items {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 12px;
    }

    .bill-items th,
    .bill-items td {
        border: 1px solid var(--inv-line);
        padding: 6px 8px;
        font-size: 10px;
        vertical-align: top;
    }

    .bill-items th {
        background: var(--inv-bg);
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .bill-items tbody tr:nth-child(even) td { background: #fafafa; }

    .bill-classic-bottom {
        display: grid;
        grid-template-columns: 1.2fr 0.8fr;
        gap: 12px;
        margin-bottom: 12px;
    }

    .bill-words {
        padding: 8px 10px;
        border: 1px solid var(--inv-line);
        background: #fafafa;
        font-size: 10px;
        line-height: 1.5;
    }

    .bill-words-label {
        display: block;
        font-size: 8px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--inv-muted);
        margin-bottom: 3px;
    }

    .bill-notes { margin-top: 8px; font-size: 9px; }

    .bill-totals-box { align-self: start; }

    .bill-grand {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 12px;
        border: 1px solid var(--inv-ink);
        background: var(--inv-bg);
        font-size: 13px;
        font-weight: 800;
    }

    .bill-footer {
        margin-top: 12px;
        padding-top: 10px;
        border-top: 1px solid var(--inv-line);
        font-size: 9px;
    }

    .bill-footer-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-bottom: 8px;
    }

    .bill-footnote {
        text-align: center;
        font-size: 8px;
        color: var(--inv-muted);
        padding-top: 6px;
        border-top: 1px solid #eee;
    }

    /* Minimal non-GST bill */
    .bill-minimal {
        padding: 14px 16px 10px;
        font-size: 10px;
        line-height: 1.45;
    }

    .bill-min-header {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        padding-bottom: 12px;
        border-bottom: 2px solid var(--inv-ink);
    }

    .bill-min-left { display: flex; gap: 10px; align-items: flex-start; flex: 1; min-width: 0; }
    .bill-min-logo { max-height: 40px; max-width: 68px; object-fit: contain; }
    .bill-min-company { font-size: 15px; font-weight: 700; line-height: 1.25; margin-bottom: 2px; }
    .bill-min-meta { color: var(--inv-muted); white-space: pre-line; font-size: 9px; }
    .bill-min-right { text-align: right; flex-shrink: 0; }
    .bill-min-label { font-size: 9px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--inv-muted); }
    .bill-min-no { font-size: 13px; font-weight: 700; margin-top: 2px; }
    .bill-min-date { font-size: 10px; color: var(--inv-muted); margin-top: 2px; }

    .bill-min-strip {
        display: flex;
        flex-wrap: wrap;
        gap: 12px 20px;
        padding: 8px 0;
        font-size: 9px;
        color: var(--inv-muted);
        border-bottom: 1px dashed #ccc;
    }

    .bill-min-parties {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px solid #ddd;
    }

    .bill-min-party-label {
        font-size: 8px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--inv-muted);
        margin-bottom: 3px;
    }

    .bill-min-party-text { font-size: 9px; color: var(--inv-muted); white-space: pre-line; }

    .bill-min-items {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
        font-size: 9px;
    }

    .bill-min-items th {
        text-align: left;
        font-size: 8px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--inv-muted);
        padding: 6px 4px;
        border-bottom: 2px solid var(--inv-ink);
    }

    .bill-min-items td {
        padding: 5px 4px;
        border-bottom: 1px solid #e5e5e5;
        vertical-align: top;
    }

    .bill-min-items tbody tr:last-child td { border-bottom: 2px solid #ddd; }

    .bill-min-bottom {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 16px;
        margin-top: 12px;
    }

    .bill-min-words { flex: 1; font-size: 9px; line-height: 1.5; }
    .bill-min-words-label { display: block; font-size: 8px; font-weight: 700; text-transform: uppercase; color: var(--inv-muted); margin-bottom: 2px; }

    .bill-min-total {
        display: flex;
        justify-content: space-between;
        gap: 20px;
        min-width: 10rem;
        padding: 8px 10px;
        border: 1px solid #ddd;
        background: #fafafa;
        font-size: 12px;
        font-weight: 800;
    }

    .bill-min-notes { margin-top: 8px; font-size: 9px; }

    /* Keep standalone document views usable on narrow screens (screen only — print stays A4). */
    @media screen and (max-width: 767px) {
        body { padding: 8px; overflow-x: hidden; }

        .toolbar { flex-direction: column; align-items: stretch; gap: 8px; }
        .toolbar > div { display: flex; flex-wrap: wrap; gap: 8px; }

        .doc, .invoice-sheet { max-width: 100%; }

        .seller-block, .min-header, .bill-classic-top, .bill-min-header, .mod-banner-inner { flex-direction: column; text-align: left; }
        .min-header-right, .bill-min-right, .mod-title-block { text-align: left; }
        .mod-banner, .mod-body, .gst-modern, .gst-minimal, .bill-classic, .bill-minimal { padding-left: 10px; padding-right: 10px; }

        .min-parties, .min-bottom, .mod-parties, .mod-summary, .bill-classic-parties, .bill-classic-bottom, .bill-footer-grid, .min-footer, .mod-footer { grid-template-columns: 1fr; }
        .mod-meta-cards { grid-template-columns: repeat(2, 1fr); }
        .bill-min-bottom { flex-direction: column; align-items: stretch; }
        .bill-min-total, .bill-meta-box { min-width: 0; }
        .amount-words-block, .notes-line, .bill-words { word-break: break-word; }
    }

    @media print {
        .no-print { display: none !important; }
        body { background: #fff; padding: 0; }
        .doc { border: 1px solid #000; box-shadow: none; max-width: none; }
        .invoice-sheet tr.data-row:nth-child(even) td { background: #fff; }
        .mod-items tbody tr:nth-child(even) td { background: #fff; }
        .mod-banner { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .mod-grand { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .mod-items thead th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .bill-items tbody tr:nth-child(even) td { background: #fff; }
        @page { size: A4; margin: 8mm; }
    }
</style>
