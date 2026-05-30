
<style>
   @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Khmer:wght@400;500;600;700;800&display=swap');

    * {
        box-sizing: border-box;
    }

    body,
button,
input,
select,
textarea,
table {
    font-family: 'Inter', 'Noto Sans Khmer', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif !important;
}

    body {
        background: #f3f6f8;
        color: #0f172a;
        font-size: 14px;
        font-weight: 500;
        line-height: 1.5;
        letter-spacing: -0.01em;
    }

    .farm-brand-title,
    .page-title,
    .top h1 {
        font-weight: 800 !important;
        letter-spacing: -0.04em;
    }

    .page-title,
    .top h1 {
        font-size: 26px !important;
        color: #0f172a;
    }

    .page-subtitle,
    .top p {
        font-size: 14px !important;
        font-weight: 500 !important;
        color: #64748b !important;
        letter-spacing: -0.01em;
    }

    .farm-menu-link {
        font-size: 14px !important;
        font-weight: 700 !important;
        letter-spacing: -0.01em;
    }
    .setting-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    margin-bottom: 16px;
}

.setting-subtitle {
    margin-top: 4px;
    color: #64748b;
    font-size: 14px;
    font-weight: 500;
}

.language-setting-box {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}

.language-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px;
    border-radius: 16px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    text-decoration: none;
    color: #0f172a;
    transition: 0.2s ease;
}

.language-card:hover {
    border-color: #16a34a;
    background: #f0fdf4;
}

.language-card.active {
    background: #ecfdf5;
    border-color: #16a34a;
    box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.12);
}

.language-code {
    min-width: 54px;
    height: 54px;
    border-radius: 14px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 900;
    color: #15803d;
}
.chart-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    margin-bottom: 14px;
}

.chart-subtitle {
    margin-top: 4px;
    color: #64748b;
    font-size: 14px;
    font-weight: 600;
}

.chart-box {
    height: 360px;
    width: 100%;
    padding: 8px;
}

.language-title {
    font-size: 16px;
    font-weight: 900;
    color: #0f172a;
}

.language-desc {
    margin-top: 3px;
    font-size: 13px;
    color: #64748b;
    font-weight: 600;
}
.page {
    position: relative;
}

.toast-alert-wrap {
    position: absolute;
    top: 18px;
    right: 18px;
    z-index: 30;
    width: min(420px, calc(100vw - 40px));
}

.toast-alert {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    border-radius: 14px;
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
    font-weight: 800;
    border: 1px solid transparent;
}

.toast-alert.success {
    background: #ecfdf5;
    color: #166534;
    border-color: #bbf7d0;
}

.toast-alert.error {
    background: #fef2f2;
    color: #991b1b;
    border-color: #fecaca;
}

.toast-icon {
    width: 28px;
    height: 28px;
    border-radius: 999px;
    display: grid;
    place-items: center;
    font-weight: 900;
    flex: 0 0 auto;
}

.toast-alert.success .toast-icon {
    background: #16a34a;
    color: #ffffff;
}

.toast-alert.error .toast-icon {
    background: #dc2626;
    color: #ffffff;
}

.toast-text {
    flex: 1;
    line-height: 1.35;
}

.toast-close {
    border: 0;
    background: transparent;
    color: inherit;
    font-size: 22px;
    font-weight: 900;
    cursor: pointer;
    line-height: 1;
}

@media (max-width: 768px) {
    .toast-alert-wrap {
        top: 12px;
        right: 12px;
        left: 12px;
        width: auto;
    }
}

@media (max-width: 768px) {
    .language-setting-box {
        grid-template-columns: 1fr;
    }
}

    .farm-menu-section {
        font-size: 12px !important;
        font-weight: 800 !important;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #94a3b8;
    }

    .summary-label {
        font-size: 13px !important;
        font-weight: 700 !important;
        color: #64748b !important;
        letter-spacing: -0.01em;
    }

    .summary-value {
        font-size: 28px !important;
        font-weight: 800 !important;
        color: #0f172a;
        letter-spacing: -0.04em;
    }

    .panel-title,
    .panel h2 {
        font-size: 18px !important;
        font-weight: 800 !important;
        color: #0f172a;
        letter-spacing: -0.03em;
    }

    label {
        font-size: 13px !important;
        font-weight: 700 !important;
        color: #1e293b;
        letter-spacing: -0.01em;
    }

    input,
    select,
    textarea {
        font-size: 14px !important;
        font-weight: 500 !important;
        color: #0f172a;
    }

    input::placeholder,
    textarea::placeholder {
        color: #94a3b8;
        font-weight: 400;
    }

    table {
        font-size: 14px !important;
    }

    table th {
        font-size: 12px !important;
        font-weight: 800 !important;
        color: #334155;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    table td {
        font-size: 14px !important;
        font-weight: 500 !important;
        color: #0f172a;
    }

    .btn,
    .mini,
    .logout-btn {
        font-weight: 800 !important;
        letter-spacing: -0.01em;
    }

    .status {
        font-size: 12px !important;
        font-weight: 800 !important;
        letter-spacing: -0.01em;
    }
</style>
<style>
    .farm-menu {
        padding: 14px;
    }

    .farm-menu-section {
        color: #64748b;
        font-size: 11px;
        text-transform: uppercase;
        font-weight: 900;
        padding: 18px 10px 8px;
        letter-spacing: .08em;
    }
    .language-switcher {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-right: 12px;
}

.lang-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 46px;
    height: 36px;
    padding: 0 10px;
    border-radius: 10px;
    background: #f1f5f9;
    color: #334155;
    text-decoration: none;
    font-size: 13px;
    font-weight: 800;
    border: 1px solid #e2e8f0;
}

.lang-btn.active {
    background: #15803d;
    color: #ffffff;
    border-color: #15803d;
}

    .farm-menu-link {
        display: flex;
        align-items: center;
        gap: 11px;
        padding: 11px 12px;
        color: #cbd5e1;
        text-decoration: none;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 800;
        margin-bottom: 5px;
        transition: .18s ease;
    }

    .farm-menu-link:hover {
        background: rgba(255,255,255,.08);
        color: white;
    }

    .farm-menu-link.active {
        background: linear-gradient(135deg, #16a34a, #166534);
        color: #ffffff;
        box-shadow: 0 10px 22px rgba(22,101,52,.28);
    }

    .farm-menu-icon {
        width: 22px;
        text-align: center;
    }

    .page {
        padding: 24px;
        background: #f4f6f9;
        min-height: calc(100vh - 68px);
        color: #111827;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        margin-bottom: 20px;
    }

    .page-title {
        font-size: 28px;
        line-height: 1.15;
        font-weight: 950;
        margin: 0;
        letter-spacing: -.04em;
        color: #0f172a;
    }

    .page-subtitle {
        margin: 7px 0 0;
        color: #6b7280;
        font-size: 14px;
        font-weight: 600;
    }

    .panel {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        padding: 20px;
        margin-bottom: 18px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .06);
    }

    .panel-title {
        margin: 0 0 16px;
        font-size: 18px;
        font-weight: 950;
        color: #0f172a;
        letter-spacing: -.02em;
    }

    .summary-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }

    .summary-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        padding: 18px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .06);
        position: relative;
        overflow: hidden;
    }

    .summary-card::before {
        content: "";
        position: absolute;
        inset: 0 0 auto 0;
        height: 4px;
        background: linear-gradient(90deg, #16a34a, #84cc16);
    }

    .summary-label {
        color: #6b7280;
        font-size: 13px;
        font-weight: 900;
    }

    .summary-value {
        font-size: 27px;
        font-weight: 950;
        margin-top: 9px;
        color: #0f172a;
        letter-spacing: -.04em;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 16px;
    }

    label {
        display: block;
        font-size: 13px;
        font-weight: 900;
        margin-bottom: 7px;
        color: #374151;
    }

    input,
    select,
    textarea {
        width: 100%;
        border: 1px solid #d1d5db;
        border-radius: 12px;
        padding: 11px 13px;
        font-size: 14px;
        outline: none;
        background: #ffffff;
        color: #111827;
        transition: .15s ease;
    }

    input:focus,
    select:focus,
    textarea:focus {
        border-color: #16a34a;
        box-shadow: 0 0 0 4px rgba(22, 163, 74, .12);
    }

    textarea {
        min-height: 96px;
        resize: vertical;
    }

    small {
        color: #dc2626;
        display: block;
        margin-top: 5px;
        font-size: 12px;
        font-weight: 700;
    }

    .btn-row,
    .actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 16px;
    }

    .btn {
        border: none;
        background: linear-gradient(135deg, #16a34a, #166534);
        color: white;
        padding: 10px 15px;
        border-radius: 12px;
        font-weight: 900;
        text-decoration: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        min-height: 40px;
        box-shadow: 0 8px 18px rgba(22,101,52,.18);
        transition: .18s ease;
    }

    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 12px 24px rgba(22,101,52,.22);
    }

    .btn.gray {
        background: #374151;
        box-shadow: none;
    }

    .btn.light {
        background: #e5e7eb;
        color: #111827;
        box-shadow: none;
    }

    .btn.red {
        background: #dc2626;
        box-shadow: none;
    }

    .alert {
        background: #dcfce7;
        color: #166534;
        padding: 13px 15px;
        border-radius: 14px;
        margin-bottom: 16px;
        font-weight: 900;
        border: 1px solid #bbf7d0;
    }

    .table-wrap {
        overflow-x: auto;
        border: 1px solid #eef2f7;
        border-radius: 14px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        min-width: 920px;
        background: white;
    }

    th {
        background: #f8fafc;
        color: #374151;
        font-size: 12px;
        text-align: left;
        padding: 13px 14px;
        border-bottom: 1px solid #e5e7eb;
        white-space: nowrap;
        text-transform: uppercase;
        letter-spacing: .04em;
        font-weight: 950;
    }

    td {
        padding: 13px 14px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 14px;
        white-space: nowrap;
        color: #111827;
    }

    tbody tr:hover {
        background: #f9fafb;
    }

    .mini {
        border: none;
        background: #2563eb;
        color: white;
        padding: 7px 10px;
        border-radius: 9px;
        cursor: pointer;
        font-weight: 900;
        font-size: 12px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-right: 4px;
    }

    .mini.danger {
        background: #dc2626;
    }

    .status {
        padding: 5px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 950;
        display: inline-flex;
        align-items: center;
    }

    .status.active,
    .status.completed {
        background: #dcfce7;
        color: #166534;
    }

    .status.inactive,
    .status.cancelled {
        background: #fee2e2;
        color: #991b1b;
    }

    .status.pending {
        background: #fef3c7;
        color: #92400e;
    }

    .empty {
        text-align: center;
        color: #6b7280;
        padding: 26px;
        font-weight: 700;
    }

    .filter-box {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        margin-bottom: 18px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .06);
        overflow: hidden;
    }

    .filter-box summary {
        list-style: none;
        cursor: pointer;
        padding: 17px 20px;
        font-size: 18px;
        font-weight: 950;
        color: #0f172a;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: white;
        border-bottom: 1px solid #f1f5f9;
    }

    .filter-box summary::-webkit-details-marker {
        display: none;
    }

    .filter-box summary::after {
        content: "＋";
        font-size: 22px;
        font-weight: 950;
        color: #166534;
    }

    .filter-box[open] summary::after {
        content: "−";
    }

    .filter-body {
        padding: 20px;
    }

    .pagination-wrap {
        margin-top: 18px;
    }

    .pagination-wrap nav {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .pagination-wrap p {
        color: #6b7280;
        font-size: 13px;
        font-weight: 700;
    }

    .pagination-wrap a,
    .pagination-wrap span {
        font-size: 13px;
    }

    @media (max-width: 1100px) {
        .summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .form-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 640px) {
        .page {
            padding: 16px;
        }

        .page-header {
            flex-direction: column;
            align-items: stretch;
        }

        .summary-grid,
        .form-grid {
            grid-template-columns: 1fr;
        }

        .page-title {
            font-size: 24px;
        }

        .summary-value {
            font-size: 24px;
        }

        .btn {
            width: 100%;
        }
    }
</style>
<style>
    /* Clean page header */
    .page-header,
    .top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 20px;
    }

    .page-header > div,
    .top > div {
        min-width: 0;
    }

    .page-title,
    .top h1 {
        font-size: 24px;
        font-weight: 900;
        color: #0f172a;
        margin: 0;
        letter-spacing: -0.03em;
    }

    .page-subtitle,
    .top p {
        margin: 6px 0 0;
        font-size: 14px;
        color: #64748b;
        font-weight: 600;
    }

    /* Clean button alignment */
    .page-actions,
    .actions,
    .btn-row {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 0;
    }

    .panel .actions,
    .panel .btn-row {
        justify-content: flex-start;
        margin-top: 16px;
    }

    .btn {
        height: 40px;
        min-width: 92px;
        padding: 0 16px;
        border: none;
        border-radius: 10px;
        background: #15803d;
        color: #ffffff;
        font-size: 14px;
        font-weight: 800;
        text-decoration: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
        box-shadow: none;
        white-space: nowrap;
    }

    .btn:hover {
        background: #166534;
        transform: none;
        box-shadow: none;
    }

    .btn.gray {
        background: #334155;
        color: #ffffff;
    }

    .btn.gray:hover {
        background: #1e293b;
    }

    .btn.light {
        background: #e5e7eb;
        color: #111827;
    }

    .btn.light:hover {
        background: #d1d5db;
    }

    .btn.red {
        background: #dc2626;
        color: white;
    }

    .btn.red:hover {
        background: #b91c1c;
    }

    /* Table action buttons */
    .table-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 6px;
        flex-wrap: nowrap;
    }

    .mini {
        height: 32px;
        min-width: 58px;
        padding: 0 10px;
        border-radius: 8px;
        border: none;
        background: #2563eb;
        color: white;
        font-size: 12px;
        font-weight: 800;
        text-decoration: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
        white-space: nowrap;
        margin: 0;
    }

    .mini:hover {
        background: #1d4ed8;
    }

    .mini.danger {
        background: #dc2626;
    }

    .mini.danger:hover {
        background: #b91c1c;
    }

    /* Logout button cleaner */
    .logout-btn {
        height: 40px;
        padding: 0 16px;
        border-radius: 10px;
        border: none;
        background: #fee2e2;
        color: #991b1b;
        font-weight: 900;
        cursor: pointer;
        white-space: nowrap;
    }

    .logout-btn:hover {
        background: #fecaca;
    }

    /* Mobile */
    @media (max-width: 700px) {
        .page-header,
        .top {
            flex-direction: column;
            align-items: stretch;
        }

        .page-actions,
        .actions,
        .btn-row {
            justify-content: flex-start;
        }

        .btn {
            width: auto;
            min-width: 100px;
        }

        .table-actions {
            justify-content: flex-start;
        }
    }
</style>