
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="BSC BSC&#174; Cloud ERP Solutions">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="theme-color" content="#0134d4">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">

    <title>BSC&#174; Cloud ERP Solutions</title>
    

    <link rel="apple-touch-icon" sizes="57x57" href="/content/favicon/apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="/content/favicon/apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="/content/favicon/apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="/content/favicon/apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="/content/favicon/apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="/content/favicon/apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="/content/favicon/apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/content/favicon/apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/content/favicon/apple-icon-180x180.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/content/favicon/android-icon-192x192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/content/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="/content/favicon/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/content/favicon/favicon-16x16.png">
    <link rel="manifest" href="/manifest.json">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="/content/favicon/ms-icon-144x144.png">
    <meta name="theme-color" content="#ffffff">
    <link href="/Content/lib/css/dx.common.css" rel="stylesheet" />
    <link href="/Content/lib/css/dx.light.css" rel="stylesheet" />
    <link href="/Content/css/inter.css" rel="stylesheet" />
    <link href="/Content/assets/plugins/global/plugins.bundle.css?v=bootstrap-5.3.8" rel="stylesheet" />
    <link href="/Content/assets/css/style.bundle.css?v=bootstrap-5.3.8" rel="stylesheet" />
<link href="/Content/css/fontawesome.css" rel="stylesheet" />
    <link href="/Content/css/duotone.css" rel="stylesheet" />
<link href="/Content/css/bscgridcolor.css" rel="stylesheet" />
    <link href="/Content/css/site?v=BRLQV2nyAELyMulHIwjep7nzdeiARmO-QkPba3aZMZ81" rel="stylesheet"/>

    
    
    <style>
        .overlay {
            height: 100%;
            width: 100%;
            display: none;
            position: fixed;
            z-index: 999999;
            top: 0;
            left: 0;
            background: url(../../Content/images/modulebg.jpg)no-repeat center center
        }

        .overlay-content {
            position: relative;
            top: 25%;
            width: 100%;
            text-align: center;
            margin-top: 30px
        }

        .overlay a {
            padding: 8px;
            text-decoration: none;
            font-size: 36px;
            color: #818181;
            display: block;
            transition: .3s
        }

            .overlay a:hover, .overlay a:focus {
                color: #f1f1f1
            }

        .overlay .closebtn {
            position: absolute;
            top: 20px;
            right: 45px;
            font-size: 60px
        }

        .btn-group-lg > .btn i, .btn.btn-lg i {
            font-size: 1.5rem;
            COLOR: WHITE;
            /*padding-right: 0.25rem;*/
        }

        .btn {
            outline: none !important;
        }

        .tooltip-inner {
            max-width: 350px;
            padding: 10px 14px;
            background-color: #1e1e2d;
            color: #fff;
            font-size: 14px;
            font-weight: 600; /* đậm hơn */
            border-radius: 8px;
            text-align: left;
            box-shadow: 0 4px 12px rgba(0,0,0,0.25);
        }

        /* màu mũi tên tooltip */
        .tooltip-custom-bold .tooltip-inner {
            /*background: linear-gradient(135deg, #4facfe, #00c6fb);*/
            background: linear-gradient(135deg, #00b09b, #0066b3);
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
            padding: 10px 16px;
            border-radius: 10px;
            box-shadow: 0 6px 18px rgba(0, 198, 251, 0.35);
            max-width: 350px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.2);
        }

        /* mũi tên */
        .tooltip-custom-bold.bs-tooltip-top .tooltip-arrow::before {
            border-top-color: #2bbcff !important;
        }

        .tooltip-custom-bold.bs-tooltip-bottom .tooltip-arrow::before {
            border-bottom-color: #2bbcff !important;
        }

        .tooltip-custom-bold.bs-tooltip-start .tooltip-arrow::before {
            border-left-color: #2bbcff !important;
        }

        .tooltip-custom-bold.bs-tooltip-end .tooltip-arrow::before {
            border-right-color: #2bbcff !important;
        }
    </style>
    <style>
    #kt_app_root > #kt_app_page.flex-column-fluid {
        flex: 0 0 auto;
    }

    #kt_app_root > #kt_app_wrapper.flex-row-fluid {
        flex: 1 auto;
    }

    #kt_app_toolbar {
        background-color: var(--kt-app-toolbar-base-bg-color, #fff) !important;
        box-shadow: 0 30px 0 0 var(--kt-app-bg-color, #F9F9F9), 0 10px 30px 0 rgba(82, 63, 105, .05);
        border-top: var(--kt-app-toolbar-base-border-top, 0);
        border-bottom: var(--kt-app-toolbar-base-border-bottom, 0);
        padding: 1rem 0 1.5rem;
    }

    .subheader,
    .kt-subheader {
        background: transparent;
        padding: 1rem 0 1.5rem;
    }

    .subheader-solid {
        background: transparent !important;
    }

    #kt_app_toolbar > #kt_app_toolbar_container,
    #kt_app_toolbar > .container-fluid,
    .subheader > .container-fluid,
    .kt-subheader .kt-container,
    .kt-subheader .container-fluid {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: nowrap;
        gap: 1rem;
        width: 100%;
        max-width: none;
        padding-left: 0;
        padding-right: 0;
    }

    #kt_app_toolbar .page-title,
    #kt_app_toolbar .kt-subheader__main,
    #kt_app_toolbar .d-flex.align-items-center.flex-wrap.mr-1,
    #kt_app_toolbar .d-flex.align-items-baseline.flex-wrap.mr-5,
    .kt-subheader__main,
    .subheader .d-flex.align-items-center.flex-wrap.mr-1,
    .subheader .d-flex.align-items-baseline.flex-wrap.mr-5 {
        display: flex;
        flex-direction: column;
        align-items: flex-start !important;
        gap: .35rem;
    }

    .kt-subheader__separator,
    .kt-subheader__separator--v {
        display: none;
    }

    #kt_app_toolbar .kt-subheader__title,
    #kt_app_toolbar h5,
    #kt_app_toolbar .page-heading,
    .kt-subheader__title,
    .subheader h5 {
        color: #181c32 !important;
        font-size: 1.35rem;
        font-weight: 700 !important;
        line-height: 1.2;
        margin: 0 !important;
    }

    #kt_app_toolbar .kt-subheader__desc,
    #kt_app_toolbar .breadcrumb,
    .kt-subheader__desc {
        color: #7e8299;
        font-size: .95rem;
        font-weight: 600;
    }

    #kt_app_toolbar .page-title {
        min-width: 220px;
    }

    #kt_app_toolbar .breadcrumb-item,
    #kt_app_toolbar .breadcrumb-item span {
        color: #7e8299;
        font-size: .925rem;
        font-weight: 600;
    }

    #kt_app_toolbar .breadcrumb,
    .subheader .breadcrumb,
    .kt-subheader .breadcrumb {
        background: transparent;
        margin: 0;
        padding: 0;
    }

    .bsc-portal-subheader {
        min-height: auto;
    }

    #kt_app_toolbar .bsc-subheader-actions {
        margin-left: auto;
        justify-content: flex-end;
    }

    @media (min-width: 992px) {
        #kt_app_content.app-content {
            padding-top: 30px;
        }

        #kt_app_toolbar .bsc-subheader-actions,
        #kt_app_toolbar > #kt_app_toolbar_container > .d-flex.align-items-center:last-child {
            flex-wrap: nowrap !important;
        }
    }

    #kt_app_toolbar .bsc-subheader-actions > [class*="col-"] {
        flex: 0 0 auto;
        width: auto;
        max-width: none;
        padding-left: 0;
        padding-right: 0;
    }

    #kt_app_toolbar .bsc-subheader-actions label,
    #kt_app_toolbar .bsc-subheader-actions label[class*="col-"] {
        width: auto;
        max-width: none;
        margin: 0 .35rem 0 0 !important;
        padding: 0 !important;
        color: #3f4254;
        font-size: .95rem;
        font-weight: 600;
        white-space: nowrap;
    }

    #kt_app_toolbar .bsc-subheader-actions .dx-texteditor,
    #kt_app_toolbar .bsc-subheader-actions .form-div {
        min-width: 170px;
    }

    #kt_app_toolbar .bsc-subheader-actions > .d-flex {
        align-items: center;
        gap: .5rem;
    }

    @media (max-width: 991.98px) {
        #kt_app_toolbar .bsc-subheader-actions {
            width: 100%;
            justify-content: flex-start;
        }
    }

    .bsc-subheader-filter {
        justify-content: flex-end;
    }

    .bsc-subheader-filter .bsc-filter-field {
        display: flex;
        align-items: center;
        gap: .5rem;
    }

    .bsc-subheader-filter .bsc-filter-field label {
        margin: 0;
        color: #3f4254;
        font-size: .95rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .bsc-subheader-filter .dx-texteditor {
        min-width: 210px;
    }

    .card-header .card-toolbar.bg-light-primary {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        /*flex: 1 1 auto;*/
        gap: .5rem;
        max-width: 100%;
        padding: .65rem .75rem;
        border-radius: .475rem;
    }

    .card-header .card-toolbar.bg-light-primary .nav {
        align-items: center;
        justify-content: flex-end;
        flex-wrap: wrap;
        gap: .5rem;
        width: 100%;
        border-bottom: 0;
    }

    .card-header .card-toolbar.bg-light-primary .nav-item {
        display: flex;
        align-items: center;
        margin: 0 !important;
    }

    .card-header .card-toolbar.bg-light-primary label,
    .card-header .card-toolbar.bg-light-primary label[class*="col-"] {
        width: auto;
        max-width: none;
        margin: 0 !important;
        padding: 0 !important;
        color: #3f4254;
        font-size: .95rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .card-header .card-toolbar.bg-light-primary .dx-texteditor,
    .card-header .card-toolbar.bg-light-primary .form-div {
        min-width: 210px;
    }

    #kt_app_toolbar .btn,
    .card-toolbar .btn,
    .kt-portlet__head-toolbar .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .35rem;
        min-height: 36px;
        white-space: nowrap;
    }

    .breadcrumb-dot .breadcrumb-item + .breadcrumb-item:before {
        content: "";
        display: inline-block;
        width: 5px;
        height: 2px;
        margin: 0 .75rem;
        vertical-align: middle;
        border-radius: 2px;
        background: #b5b5c3;
    }

    #kt_app_toolbar .kt-subheader__toolbar,
    #kt_app_toolbar .kt-subheader__wrapper,
    #kt_app_toolbar .kt-subheader__toolbar-wrapper,
    #kt_app_toolbar > #kt_app_toolbar_container > .d-flex.align-items-center:last-child,
    .kt-subheader__toolbar,
    .kt-subheader__wrapper,
    .kt-subheader__toolbar-wrapper,
    .kt-subheader__toolbar-wrapper .kt-subheader__toolbar-wrapper,
    .subheader .d-flex.align-items-center {
        display: flex;
        align-items: center;
        flex-wrap: nowrap;
        gap: .5rem;
    }

    .kt-portlet,
    .card.card-custom {
        border: 0;
        border-radius: .625rem;
        box-shadow: 0 .1rem 1rem .25rem rgba(0, 0, 0, .05);
    }

    .kt-portlet:not([class*="bg-"]),
    .card.card-custom:not([class*="bg-"]) {
        background: #fff;
    }

    .kt-portlet__head,
    .card.card-custom .card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        min-height: 70px;
        padding: 1.25rem 1.75rem;
        border-bottom: 1px solid #eff2f5;
        background: transparent;
    }

    .kt-portlet__head-label,
    .card.card-custom .card-title,
    .kt-portlet__head-toolbar,
    .kt-portlet__head-wrapper,
    .kt-portlet__head-actions {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .5rem;
    }

    .kt-portlet__body,
    .card.card-custom .card-body {
        padding: 1.5rem 1.75rem;
    }

    .card-toolbar.bg-light-primary {
        max-width: 100%;
        overflow-x: auto;
        padding: .55rem .75rem;
        border-radius: .5rem;
        background-color: #f1f8ff !important;
    }

    .card-toolbar.bg-light-primary .nav {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .5rem .75rem;
        margin: 0;
        border: 0;
    }

    .card-toolbar.bg-light-primary .nav-item {
        display: flex;
        align-items: center;
        margin: 0 !important;
    }

    .card-toolbar.bg-light-primary label {
        margin: 0;
        padding: 0 !important;
        color: #3f4254;
        font-weight: 600;
        white-space: nowrap;
    }

    .card-toolbar.bg-light-primary .dx-texteditor {
        min-width: 180px;
    }

    .kt-portlet__head-title,
    .card.card-custom .card-label {
        color: #181c32 !important;
        font-weight: 700 !important;
        margin: 0;
    }

    .card.card-custom .card-title {
        margin: 0;
    }

    .card.card-custom .card-icon {
        display: inline-flex;
        align-items: center;
        margin-right: .75rem;
    }

    .card-stretch {
        height: calc(100% - 1.5rem);
    }

    .gutter-b {
        margin-bottom: 1.5rem;
    }

    .font-weight-bold,
    .font-weight-bolder {
        font-weight: 700 !important;
    }

    .font-weight-normal {
        font-weight: 400 !important;
    }

    .font-size-sm,
    .btn-font-sm {
        font-size: .925rem !important;
    }

    .font-size-lg {
        font-size: 1.08rem !important;
    }

    .font-size-h5 {
        font-size: 1.25rem !important;
    }

    .text-dark-75 {
        color: #3f4254 !important;
    }

    .text-brand {
        color: var(--bs-primary) !important;
    }

    .text-hover-primary:hover {
        color: var(--bs-primary) !important;
    }

    .btn.btn-label,
    .btn.btn-label-brand,
    .btn.btn-outline-label,
    .btn.btn-outline-label.btn-label-brand {
        color: var(--bs-primary) !important;
        background: #f1f8ff !important;
        border-color: #f1f8ff !important;
    }

    .btn.btn-clean {
        border-color: transparent;
        background: transparent;
    }

    .btn.btn-block {
        display: block;
        width: 100%;
    }

    .btn-light-primary {
        color: var(--bs-primary) !important;
        background-color: #f1f8ff !important;
        border-color: #f1f8ff !important;
    }

    .btn-light-primary:hover,
    .btn-hover-primary:hover,
    .btn-hover-light-primary:hover {
        color: #fff !important;
        background-color: var(--bs-primary) !important;
        border-color: var(--bs-primary) !important;
    }

    .btn-xs {
        padding: .25rem .5rem;
        font-size: .75rem;
        border-radius: .35rem;
    }

    .head-btn:not(.btn) {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .4rem;
        min-height: 34px;
        padding: .55rem .9rem;
        color: var(--bs-primary) !important;
        font-weight: 700;
        line-height: 1.2;
        text-decoration: none !important;
        white-space: nowrap;
        background: #f1f8ff;
        border: 1px solid #f1f8ff;
        border-radius: .475rem;
    }

    .head-btn:not(.btn):hover {
        color: #fff !important;
        background: var(--bs-primary);
        border-color: var(--bs-primary);
    }

    .btn:not(.btn-icon) > i,
    .head-btn > i {
        margin-right: .35rem;
    }

    .btn.btn-icon > i {
        margin-right: 0;
    }

    .input-group-prepend,
    .input-group-append {
        display: flex;
    }

    .input-group > .input-group-prepend > .input-group-text {
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }

    .input-group > .input-group-prepend + .form-control {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }

    .mr-1 { margin-right: .25rem !important; }
    .mr-2, .kt-margin-r-10 { margin-right: .5rem !important; }
    .mr-3 { margin-right: .75rem !important; }
    .mr-4 { margin-right: 1rem !important; }
    .mr-5 { margin-right: 1.25rem !important; }
    .ml-1 { margin-left: .25rem !important; }
    .ml-2 { margin-left: .5rem !important; }
    .ml-3 { margin-left: .75rem !important; }
    .ml-4 { margin-left: 1rem !important; }
    .ml-5 { margin-left: 1.25rem !important; }
    .pr-1 { padding-right: .25rem !important; }
    .pr-2 { padding-right: .5rem !important; }
    .pr-3 { padding-right: .75rem !important; }
    .pr-4 { padding-right: 1rem !important; }
    .pr-5 { padding-right: 1.25rem !important; }
    .pl-1 { padding-left: .25rem !important; }
    .pl-2 { padding-left: .5rem !important; }
    .pl-3 { padding-left: .75rem !important; }
    .pl-4 { padding-left: 1rem !important; }
    .pl-5 { padding-left: 1.25rem !important; }

    @media (min-width: 1200px) {
        .pr-xl-4 { padding-right: 1rem !important; }
        .pl-xl-4 { padding-left: 1rem !important; }
    }

    .symbol {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        position: relative;
        flex-shrink: 0;
    }

    .symbol .symbol-label {
        width: 100%;
        height: 100%;
        border-radius: inherit;
        background-size: cover;
        background-position: center;
    }

    .symbol-60 {
        width: 60px;
        height: 60px;
    }

    .symbol-45 {
        width: 45px;
        height: 45px;
    }

    .symbol-100,
    .symbol-xxl-100 {
        width: 100px;
        height: 100px;
    }

    .symbol-badge {
        position: absolute;
        right: 0;
        bottom: 0;
        width: .85rem;
        height: .85rem;
        border: 2px solid #fff;
        border-radius: 50%;
    }

    .navi,
    .navi .nav {
        margin: 0;
        padding: 0;
    }

    .navi .nav-link,
    .navi-item .nav-link {
        display: flex;
        align-items: center;
        gap: .5rem;
        color: #5e6278;
        border-radius: .475rem;
        padding: .75rem 1rem;
    }

    .navi .nav-link.active,
    .navi .nav-link:hover {
        color: var(--bs-primary);
        background: #f1f8ff;
    }

    .navi-icon {
        display: inline-flex;
        align-items: center;
    }

    .navi-text {
        min-width: 0;
    }

    .modal-dialog-full-width,
    .modal-full {
        width: calc(100% - 2rem);
        max-width: none;
    }

    .modal-sticky-lg {
        max-width: 720px;
    }

    .modal .kt-portlet {
        margin: 0;
        border-radius: 0;
        box-shadow: none;
    }

    .modal .kt-portlet__head {
        min-height: 64px;
        padding: 1rem 1.5rem;
    }

    .modal .kt-portlet__body {
        padding: 1.5rem;
    }

    .modal .modal-content {
        border: 0;
        border-radius: .625rem;
        box-shadow: 0 .5rem 1.75rem rgba(0, 0, 0, .14);
    }

    .modal .modal-header {
        align-items: center;
        gap: .75rem;
    }

    .modal.swal2-container {
        overflow-x: hidden;
        overflow-y: auto;
        padding: 0 !important;
    }

    .modal.swal2-container .modal-dialog {
        margin: 1.75rem auto;
    }

    .custom-file {
        position: relative;
        display: block;
        width: 100%;
        height: calc(1.5em + 1.3rem + 2px);
    }

    .custom-file-input {
        position: relative;
        z-index: 2;
        width: 100%;
        height: 100%;
        margin: 0;
        opacity: 0;
    }

    .custom-file-label {
        position: absolute;
        top: 0;
        right: 0;
        left: 0;
        z-index: 1;
        height: 100%;
        padding: .65rem 1rem;
        overflow: hidden;
        color: #5e6278;
        background-color: #fff;
        border: 1px solid #e4e6ef;
        border-radius: .475rem;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    .custom-file-label::after {
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        z-index: 3;
        display: block;
        padding: .65rem 1rem;
        color: #5e6278;
        content: "Browse";
        background-color: #f5f8fa;
        border-left: inherit;
        border-radius: 0 .475rem .475rem 0;
    }

    .bsc-grid,
    .dx-datagrid {
        min-width: 0;
    }

    #kt_quick_user.offcanvas {
        position: fixed;
        top: 0;
        right: -420px;
        bottom: 0;
        z-index: 1060;
        display: block;
        visibility: visible;
        transform: none;
        width: 390px;
        max-width: calc(100vw - 1rem);
        overflow-y: auto;
        background: #fff;
        box-shadow: -0.5rem 0 1.5rem rgba(0, 0, 0, .12);
        transition: right .25s ease;
    }

    #kt_quick_user.offcanvas.offcanvas-on {
        right: 0;
    }

    body.quick-user-open {
        overflow: hidden;
    }

    .bsc-quick-user-overlay {
        position: fixed;
        inset: 0;
        z-index: 1055;
        background: rgba(0, 0, 0, .35);
    }

    #kt_quick_user .bg-hover-light-primary:hover {
        background: #f1f8ff;
    }

    #kt_quick_user .bg-hover-light-primary {
        border-radius: .475rem;
        padding: .75rem;
        margin-bottom: .35rem;
    }

    @media (max-width: 991.98px) {
        #kt_app_toolbar > #kt_app_toolbar_container,
        #kt_app_toolbar > .container-fluid,
        .subheader > .container-fluid,
        .kt-subheader .kt-container,
        .kt-subheader .container-fluid {
            align-items: flex-start;
            flex-direction: column;
            flex-wrap: wrap;
        }

        #kt_app_toolbar .kt-subheader__toolbar,
        #kt_app_toolbar .kt-subheader__wrapper,
        #kt_app_toolbar .kt-subheader__toolbar-wrapper,
        #kt_app_toolbar > #kt_app_toolbar_container > .d-flex.align-items-center:last-child,
        .kt-subheader__toolbar,
        .kt-subheader__wrapper,
        .kt-subheader__toolbar-wrapper,
        .subheader .d-flex.align-items-center {
            flex-wrap: wrap;
            width: 100%;
        }

        .bsc-subheader-filter {
            justify-content: flex-start;
        }

        .bsc-subheader-filter .bsc-filter-field {
            width: 100%;
            align-items: flex-start;
            flex-direction: column;
        }

        .bsc-subheader-filter .bsc-filter-field .dx-widget,
        .bsc-subheader-filter .bsc-filter-field .dx-texteditor,
        .bsc-subheader-filter .bsc-filter-field > div,
        .card-header .card-toolbar.bg-light-primary .dx-widget,
        .card-header .card-toolbar.bg-light-primary .dx-texteditor,
        .card-header .card-toolbar.bg-light-primary .nav-item,
        .card-header .card-toolbar.bg-light-primary .nav-item > div {
            width: 100% !important;
        }

        .kt-portlet__head,
        .card.card-custom .card-header {
            align-items: flex-start;
            flex-direction: column;
        }

        .modal-dialog-full-width,
        .modal-full {
            width: calc(100% - 1rem);
            margin-left: .5rem;
            margin-right: .5rem;
        }
    }
</style>


</head>
<body id="kt_app_body" data-kt-app-layout="dark-header" data-kt-app-header-fixed="true" data-kt-app-toolbar-enabled="true" data-kt-app-toolbar-fixed="true" class="app-default">
    <input type="hidden" id="culture" value="vi" />
    <input type="hidden" id="returnUrl" value="/TangCa/DuyetTangCaThucTe" />
    <input type="hidden" id="moduleName" value="Nh&#226;n sự" />
    <input type="hidden" id="homeTitle" value="Trang chủ" />
    <input type="hidden" id="appTitle" value="BSC&#174; Cloud ERP Solutions" />
    <input name="__RequestVerificationToken" type="hidden" value="0qHxTtPvqN3Js-BL-pd3ky4arb2y6c1hjdpkPrQrNLFlZXHFVa3iZLolX9TWDmFj1GiNmV_hSEAjf2BHGqvz70AD3X-ST431AOaa8ZGrYDk1" />
    <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
        <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
            <div id="kt_app_header" class="app-header">
                <div class="app-container container-fluid d-flex align-items-stretch justify-content-between" id="kt_app_header_container">
                    <div class="d-flex align-items-center flex-grow-1 flex-lg-grow-0 me-lg-15">
                        <a href="/">
                            <img alt="BSC&#174; Cloud ERP Solutions" src="/Content/images/logo_white.svg" class="h-20px h-lg-40px app-sidebar-logo-default" />
                        </a>
                    </div>

                    <div class="d-flex align-items-stretch justify-content-between flex-lg-grow-1" id="kt_app_header_wrapper">

                        <div class="app-header-menu app-header-mobile-drawer align-items-stretch" data-kt-drawer="true" data-kt-drawer-name="app-header-menu" data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="225px" data-kt-drawer-direction="end" data-kt-drawer-toggle="#kt_app_header_menu_toggle" data-kt-swapper="true" data-kt-swapper-mode="{default: 'append', lg: 'prepend'}" data-kt-swapper-parent="{default: '#kt_app_body', lg: '#kt_app_header_wrapper'}">
                            <style>
    #kt_app_header_menu .custom-has-submenu > .menu-sub {
        z-index: 108 !important;
        min-width: 250px;
    }
</style>
<div class="menu menu-rounded menu-column menu-lg-row my-5 my-lg-0 align-items-stretch fw-semibold px-2 px-lg-0" id="kt_app_header_menu" data-kt-menu="true">
                    <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="bottom-start" class="menu-item menu-lg-down-accordion menu-sub-lg-down-indention me-0 me-lg-2">
                        <span class="menu-link">
                            <span class="menu-icon">
                                <i class="fad fa-file fs-3"></i>
                            </span>
                            <span class="menu-title">Th&#244;ng tin</span>
                            <span class="menu-arrow"></span>
                        </span>
                        <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown px-lg-2 py-lg-4 w-lg-250px">
                                    <div class="menu-item">
                                        <a class="menu-link" href="/HoSo/HoSo" menu-lever="1">
                                            <span class="menu-icon">
                                                <i class="fas fa-user fs-3"></i>
                                            </span>
                                            <span class="menu-title">Hồ sơ</span>
                                        </a>
                                    </div>
                                    <div class="menu-item">
                                        <a class="menu-link" href="/DangKy/DangKy-ThanNhan" menu-lever="1">
                                            <span class="menu-icon">
                                                <i class="fas fa-address-book fs-3"></i>
                                            </span>
                                            <span class="menu-title">Hồ sơ th&#226;n nh&#226;n</span>
                                        </a>
                                    </div>
                                    <div class="menu-item">
                                        <a class="menu-link" href="/NghiPhep/PhepNam" menu-lever="1">
                                            <span class="menu-icon">
                                                <i class="fas fa-file-check fs-3"></i>
                                            </span>
                                            <span class="menu-title">Bảng tổng hợp ng&#224;y ph&#233;p</span>
                                        </a>
                                    </div>
                        </div>
                    </div>
                    <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="bottom-start" class="menu-item menu-lg-down-accordion menu-sub-lg-down-indention me-0 me-lg-2">
                        <span class="menu-link">
                            <span class="menu-icon">
                                <i class="fa-duotone fa-file-spreadsheet fs-3"></i>
                            </span>
                            <span class="menu-title">Chấm c&#244;ng</span>
                            <span class="menu-arrow"></span>
                        </span>
                        <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown px-lg-2 py-lg-4 w-lg-250px">
                                    <div class="menu-item">
                                        <a class="menu-link" href="/ChamCong/BangCong" menu-lever="1">
                                            <span class="menu-icon">
                                                <i class="flaticon-stopwatch  fs-3"></i>
                                            </span>
                                            <span class="menu-title">Chấm c&#244;ng</span>
                                        </a>
                                    </div>
                                    <div class="menu-item">
                                        <a class="menu-link" href="/ChamCong/LichSuChamCongNgay" menu-lever="1">
                                            <span class="menu-icon">
                                                <i class="fas fa-computer-classic fs-3"></i>
                                            </span>
                                            <span class="menu-title">Lịch sử chấm c&#244;ng</span>
                                        </a>
                                    </div>
                                    <div class="menu-item">
                                        <a class="menu-link" href="/Portal/ChamCong/TheoDoiOT" menu-lever="1">
                                            <span class="menu-icon">
                                                <i class="fal fa-stopwatch fs-3"></i>
                                            </span>
                                            <span class="menu-title">Tổng hợp tăng ca</span>
                                        </a>
                                    </div>
                        </div>
                    </div>
                    <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="bottom-start" class="menu-item menu-lg-down-accordion menu-sub-lg-down-indention me-0 me-lg-2">
                        <span class="menu-link">
                            <span class="menu-icon">
                                <i class="fal fa-money-check-edit fs-3"></i>
                            </span>
                            <span class="menu-title">Đăng k&#253;</span>
                            <span class="menu-arrow"></span>
                        </span>
                        <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown px-lg-2 py-lg-4 w-lg-250px">
                                    <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="right-start" class="menu-item menu-lg-down-accordion menu-sub-lg-down-indention custom-has-submenu">
                                        <a class="menu-link" href="javascript:;" menu-lever="1">
                                            <span class="menu-icon">
                                                <i class="fal fa-money-check-edit fs-3"></i>
                                            </span>
                                            <span class="menu-title">Đơn xin ph&#233;p/ C&#244;ng T&#225;c</span>
                                            <span class="menu-arrow"></span>
                                        </a>
                                        <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown px-lg-2 py-lg-4 w-lg-250px">
                                                <div class="menu-item">
                                                    <a class="menu-link" href="/NghiPhep/DonXinPhep" menu-lever="2">
                                                        <span class="menu-icon">
                                                            <i class="far fa-file-alt fs-3"></i>
                                                        </span>
                                                        <span class="menu-title">Đơn xin ph&#233;p</span>
                                                    </a>
                                                </div>
                                                <div class="menu-item">
                                                    <a class="menu-link" href="/CongTac/DonXinCongTac" menu-lever="2">
                                                        <span class="menu-icon">
                                                            <i class="fas fa-briefcase fs-3"></i>
                                                        </span>
                                                        <span class="menu-title">Đơn c&#244;ng t&#225;c</span>
                                                    </a>
                                                </div>
                                        </div>
                                    </div>
                                    <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="right-start" class="menu-item menu-lg-down-accordion menu-sub-lg-down-indention custom-has-submenu">
                                        <a class="menu-link" href="javascript:;" menu-lever="1">
                                            <span class="menu-icon">
                                                <i class="far fa-stopwatch fs-3"></i>
                                            </span>
                                            <span class="menu-title">Tăng ca</span>
                                            <span class="menu-arrow"></span>
                                        </a>
                                        <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown px-lg-2 py-lg-4 w-lg-250px">
                                                <div class="menu-item">
                                                    <a class="menu-link" href="/TangCa/TangCaKeHoach" menu-lever="2">
                                                        <span class="menu-icon">
                                                            <i class="far fa-stopwatch fs-3"></i>
                                                        </span>
                                                        <span class="menu-title">Tăng ca kế hoạch</span>
                                                    </a>
                                                </div>
                                                <div class="menu-item">
                                                    <a class="menu-link" href="/TangCa/TangCaThucTe" menu-lever="2">
                                                        <span class="menu-icon">
                                                            <i class="fal fa-business-time fs-3"></i>
                                                        </span>
                                                        <span class="menu-title">Tăng ca thực tế</span>
                                                    </a>
                                                </div>
                                        </div>
                                    </div>
                                    <div class="menu-item">
                                        <a class="menu-link" href="/CongCom/DangKyLichCom" menu-lever="1">
                                            <span class="menu-icon">
                                                <i class="fal fa-clipboard-check fs-3"></i>
                                            </span>
                                            <span class="menu-title">Lịch cơm</span>
                                        </a>
                                    </div>
                                    <div class="menu-item">
                                        <a class="menu-link" href="/DangKy/Chuyen-Code-Bo-Phan" menu-lever="1">
                                            <span class="menu-icon">
                                                <i class="fas fa-exchange fs-3"></i>
                                            </span>
                                            <span class="menu-title">Chuyển bộ phận</span>
                                        </a>
                                    </div>
                                    <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="right-start" class="menu-item menu-lg-down-accordion menu-sub-lg-down-indention custom-has-submenu">
                                        <a class="menu-link" href="javascript:;" menu-lever="1">
                                            <span class="menu-icon">
                                                <i class="fa-solid fa-person-pregnant fs-3"></i>
                                            </span>
                                            <span class="menu-title">Theo d&#245;i mang thai</span>
                                            <span class="menu-arrow"></span>
                                        </a>
                                        <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown px-lg-2 py-lg-4 w-lg-250px">
                                                <div class="menu-item">
                                                    <a class="menu-link" href="/DangKy/DangKy-QuaTrinhThaiSan" menu-lever="2">
                                                        <span class="menu-icon">
                                                            <i class="far fa-bell fs-3"></i>
                                                        </span>
                                                        <span class="menu-title">Th&#244;ng b&#225;o mang thai</span>
                                                    </a>
                                                </div>
                                                <div class="menu-item">
                                                    <a class="menu-link" href="/CheDo/DangKy-VeSomCoNho" menu-lever="2">
                                                        <span class="menu-icon">
                                                            <i class="far fa-house fs-3"></i>
                                                        </span>
                                                        <span class="menu-title">Về sớm con nhỏ</span>
                                                    </a>
                                                </div>
                                        </div>
                                    </div>
                                    <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="right-start" class="menu-item menu-lg-down-accordion menu-sub-lg-down-indention custom-has-submenu">
                                        <a class="menu-link" href="javascript:;" menu-lever="1">
                                            <span class="menu-icon">
                                                <i class="far fa-wallet fs-3"></i>
                                            </span>
                                            <span class="menu-title">Trợ cấp</span>
                                            <span class="menu-arrow"></span>
                                        </a>
                                        <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown px-lg-2 py-lg-4 w-lg-250px">
                                                <div class="menu-item">
                                                    <a class="menu-link" href="/TroCap/DangKy-TroCapSinhCon" menu-lever="2">
                                                        <span class="menu-icon">
                                                            <i class="fas fa-baby fs-3"></i>
                                                        </span>
                                                        <span class="menu-title">Trợ cấp sinh con</span>
                                                    </a>
                                                </div>
                                                <div class="menu-item">
                                                    <a class="menu-link" href="/TroCap/DangKy-TroCapNuoiCon" menu-lever="2">
                                                        <span class="menu-icon">
                                                            <i class="fa fa-family fs-3"></i>
                                                        </span>
                                                        <span class="menu-title">Trợ cấp nu&#244;i con</span>
                                                    </a>
                                                </div>
                                                <div class="menu-item">
                                                    <a class="menu-link" href="/TroCap/DangKyTroCap-NgonNgu" menu-lever="2">
                                                        <span class="menu-icon">
                                                            <i class="far fa-language fs-3"></i>
                                                        </span>
                                                        <span class="menu-title">Ng&#244;n ngữ</span>
                                                    </a>
                                                </div>
                                                <div class="menu-item">
                                                    <a class="menu-link" href="/TroCap/DangKyTroCap-NgayNghi" menu-lever="2">
                                                        <span class="menu-icon">
                                                            <i class="far fa-calendar-day fs-3"></i>
                                                        </span>
                                                        <span class="menu-title">Hiếu hỷ, ốm đau</span>
                                                    </a>
                                                </div>
                                        </div>
                                    </div>
                                    <div class="menu-item">
                                        <a class="menu-link" href="/NghiViec/DonXinNghiViec" menu-lever="1">
                                            <span class="menu-icon">
                                                <i class="fas fa-file-alt fs-3"></i>
                                            </span>
                                            <span class="menu-title">Đơn xin th&#244;i việc</span>
                                        </a>
                                    </div>
                        </div>
                    </div>
                    <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="bottom-start" class="menu-item menu-lg-down-accordion menu-sub-lg-down-indention me-0 me-lg-2">
                        <span class="menu-link">
                            <span class="menu-icon">
                                <i class="fas fa-file-signature fs-3"></i>
                            </span>
                            <span class="menu-title">Duyệt đơn</span>
                            <span class="menu-arrow"></span>
                        </span>
                        <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown px-lg-2 py-lg-4 w-lg-250px">
                                    <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="right-start" class="menu-item menu-lg-down-accordion menu-sub-lg-down-indention custom-has-submenu">
                                        <a class="menu-link" href="javascript:;" menu-lever="1">
                                            <span class="menu-icon">
                                                <i class="far fa-file-alt fs-3"></i>
                                            </span>
                                            <span class="menu-title">Đơn xin ph&#233;p/ C&#244;ng T&#225;c</span>
                                            <span class="menu-arrow"></span>
                                        </a>
                                        <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown px-lg-2 py-lg-4 w-lg-250px">
                                                <div class="menu-item">
                                                    <a class="menu-link" href="/NghiPhep/DuyetPhep" menu-lever="2">
                                                        <span class="menu-icon">
                                                            <i class="far fa-file-alt fs-3"></i>
                                                        </span>
                                                        <span class="menu-title">Duyệt ph&#233;p</span>
                                                    </a>
                                                </div>
                                                <div class="menu-item">
                                                    <a class="menu-link" href="/CongTac/DuyetCongTac" menu-lever="2">
                                                        <span class="menu-icon">
                                                            <i class="fas fa-briefcase fs-3"></i>
                                                        </span>
                                                        <span class="menu-title">Duyệt đơn c&#244;ng t&#225;c</span>
                                                    </a>
                                                </div>
                                        </div>
                                    </div>
                                    <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="right-start" class="menu-item menu-lg-down-accordion menu-sub-lg-down-indention custom-has-submenu">
                                        <a class="menu-link" href="javascript:;" menu-lever="1">
                                            <span class="menu-icon">
                                                <i class="far fa-stopwatch fs-3"></i>
                                            </span>
                                            <span class="menu-title">Tăng ca</span>
                                            <span class="menu-arrow"></span>
                                        </a>
                                        <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown px-lg-2 py-lg-4 w-lg-250px">
                                                <div class="menu-item">
                                                    <a class="menu-link" href="/TangCa/DuyetTangCaKeHoach" menu-lever="2">
                                                        <span class="menu-icon">
                                                            <i class="far fa-stopwatch fs-3"></i>
                                                        </span>
                                                        <span class="menu-title">Duyệt tăng ca kế hoạch</span>
                                                    </a>
                                                </div>
                                                <div class="menu-item">
                                                    <a class="menu-link" href="/TangCa/DuyetTangCaThucTe" menu-lever="2">
                                                        <span class="menu-icon">
                                                            <i class="fal fa-business-time fs-3"></i>
                                                        </span>
                                                        <span class="menu-title">Duyệt tăng ca thực tế</span>
                                                    </a>
                                                </div>
                                        </div>
                                    </div>
                                    <div class="menu-item">
                                        <a class="menu-link" href="/ChamCong/DuyetPhanHoiChamCong" menu-lever="1">
                                            <span class="menu-icon">
                                                <i class="fas fa-clipboard-list fs-3"></i>
                                            </span>
                                            <span class="menu-title">Duyệt phản hồi chấm c&#244;ng</span>
                                        </a>
                                    </div>
                                    <div class="menu-item">
                                        <a class="menu-link" href="/ChuyenCode/Chuyen-Code-Bo-Phan" menu-lever="1">
                                            <span class="menu-icon">
                                                <i class="fas fa-exchange fs-3"></i>
                                            </span>
                                            <span class="menu-title">Chuyển bộ phận</span>
                                        </a>
                                    </div>
                                    <div class="menu-item">
                                        <a class="menu-link" href="/ThongBao/Thong-Bao-Code-Bo-Phan" menu-lever="1">
                                            <span class="menu-icon">
                                                <i class="fas fa-random fs-3"></i>
                                            </span>
                                            <span class="menu-title">Th&#244;ng b&#225;o code bộ phận</span>
                                        </a>
                                    </div>
                                    <div class="menu-item">
                                        <a class="menu-link" href="/DanhSach/Phe-Duyet-Tai-Ky-Hop-Dong" menu-lever="1">
                                            <span class="menu-icon">
                                                <i class="far fa-address-book fs-3"></i>
                                            </span>
                                            <span class="menu-title">X&#225;c nhận hợp đồng</span>
                                        </a>
                                    </div>
                                    <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="right-start" class="menu-item menu-lg-down-accordion menu-sub-lg-down-indention custom-has-submenu">
                                        <a class="menu-link" href="javascript:;" menu-lever="1">
                                            <span class="menu-icon">
                                                <i class="fa-solid fa-person-pregnant fs-3"></i>
                                            </span>
                                            <span class="menu-title">Theo d&#245;i mang thai</span>
                                            <span class="menu-arrow"></span>
                                        </a>
                                        <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown px-lg-2 py-lg-4 w-lg-250px">
                                                <div class="menu-item">
                                                    <a class="menu-link" href="/ThaiSan/PheDuyet-QuaTrinhThaiSan" menu-lever="2">
                                                        <span class="menu-icon">
                                                            <i class="far fa-bell fs-3"></i>
                                                        </span>
                                                        <span class="menu-title">Th&#244;ng b&#225;o mang thai</span>
                                                    </a>
                                                </div>
                                                <div class="menu-item">
                                                    <a class="menu-link" href="/CheDo/PheDuyet-VeSomCoNho" menu-lever="2">
                                                        <span class="menu-icon">
                                                            <i class="far fa-house fs-3"></i>
                                                        </span>
                                                        <span class="menu-title">Về sớm con nhỏ</span>
                                                    </a>
                                                </div>
                                        </div>
                                    </div>
                                    <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="right-start" class="menu-item menu-lg-down-accordion menu-sub-lg-down-indention custom-has-submenu">
                                        <a class="menu-link" href="javascript:;" menu-lever="1">
                                            <span class="menu-icon">
                                                <i class="far fa-wallet fs-3"></i>
                                            </span>
                                            <span class="menu-title">Trợ cấp</span>
                                            <span class="menu-arrow"></span>
                                        </a>
                                        <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown px-lg-2 py-lg-4 w-lg-250px">
                                                <div class="menu-item">
                                                    <a class="menu-link" href="/TroCap/PheDuyet-TroCapSinhCon" menu-lever="2">
                                                        <span class="menu-icon">
                                                            <i class="fas fa-baby fs-3"></i>
                                                        </span>
                                                        <span class="menu-title">Trợ cấp sinh con</span>
                                                    </a>
                                                </div>
                                                <div class="menu-item">
                                                    <a class="menu-link" href="/TroCap/PheDuyet-TroCapNuoiCon" menu-lever="2">
                                                        <span class="menu-icon">
                                                            <i class="fa fa-family fs-3"></i>
                                                        </span>
                                                        <span class="menu-title">Trợ cấp nu&#244;i con</span>
                                                    </a>
                                                </div>
                                                <div class="menu-item">
                                                    <a class="menu-link" href="/TroCap/PheDuyet-TroCapNgonNgu" menu-lever="2">
                                                        <span class="menu-icon">
                                                            <i class="far fa-language fs-3"></i>
                                                        </span>
                                                        <span class="menu-title">Ng&#244;n ngữ</span>
                                                    </a>
                                                </div>
                                                <div class="menu-item">
                                                    <a class="menu-link" href="/TroCap/PheDuyet-TroCapNgayNghi" menu-lever="2">
                                                        <span class="menu-icon">
                                                            <i class="far fa-calendar-day fs-3"></i>
                                                        </span>
                                                        <span class="menu-title">Hiếu hỷ, ốm đau</span>
                                                    </a>
                                                </div>
                                        </div>
                                    </div>
                                    <div class="menu-item">
                                        <a class="menu-link" href="/NghiViec/DuyetDonXinNghiViec" menu-lever="1">
                                            <span class="menu-icon">
                                                <i class="fas fa-file-alt fs-3"></i>
                                            </span>
                                            <span class="menu-title">Đơn xin th&#244;i việc</span>
                                        </a>
                                    </div>
                        </div>
                    </div>
                    <div data-kt-menu-placement="bottom-start" class="menu-item  me-0 me-lg-2">
                        <a class="menu-link" href="/BangLuong/BangLuong" menu-lever="0">
                            <span class="menu-icon">
                                <i class="fas fa-wallet fs-3"></i>
                            </span>
                            <span class="menu-title">Bảng lương</span>
                        </a>
                    </div>
                    <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="bottom-start" class="menu-item menu-lg-down-accordion menu-sub-lg-down-indention me-0 me-lg-2">
                        <span class="menu-link">
                            <span class="menu-icon">
                                <i class="flaticon-interface-3 fs-3"></i>
                            </span>
                            <span class="menu-title">Tin tức</span>
                            <span class="menu-arrow"></span>
                        </span>
                        <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown px-lg-2 py-lg-4 w-lg-250px">
                                    <div class="menu-item">
                                        <a class="menu-link" href="/ThongBao/ThongBao" menu-lever="1">
                                            <span class="menu-icon">
                                                <i class=" flaticon-alert fs-3"></i>
                                            </span>
                                            <span class="menu-title">Th&#244;ng b&#225;o</span>
                                        </a>
                                    </div>
                        </div>
                    </div>

</div>

                        </div>

                            <div class="app-navbar flex-shrink-0">
                                <div class="app-navbar-item ms-1 ms-lg-3">
                                    <button type="button" class="btn btn-icon btn-clean btn-dropdown btn-lg bsc-web-notification-button" onclick="OpenWebNotifications()" title="Notification">
                                        <i class="flaticon2-notification"></i>
                                        <span id="webNotificationBadge" class="bsc-web-notification-badge" style="display: none;">0</span>
                                    </button>
                                </div>
                                <div class="app-navbar-item ms-1 ms-lg-3" data-toggle="kt-tooltip" title="" data-placement="top" data-original-title="Quick panel" id="qp-module">
                                    <div class="btn btn-icon btn-clean btn-dropdown btn-lg mr-1 pulse pulse-primary" onclick="openModule()">
                                        <span class="kt-header__topbar-icon kt-header__topbar-mod" id="kt_quick_panel_toggler_btn"><i class="flaticon-squares"></i></span>
                                        <span class="pulse-ring"></span>
                                    </div>
                                </div>
                                <div class="app-navbar-item ms-1 ms-lg-3">
                                    <div class="btn btn-icon btn-custom btn-icon-muted btn-active-light btn-active-color-primary w-35px h-35px w-md-40px h-md-40px" data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end">
                                        <span class="symbol symbol-20px">
                                            <img class="rounded" src="/Content/images/flags/vi.svg" alt="" />
                                        </span>
                                    </div>
                                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-primary fw-semibold py-4 fs-6 w-175px" data-kt-menu="true" id="drop-lang">
                                                <div class="menu-item px-3">
                                                    <a href="javascript:void(0)" id="vi" class="menu-link d-flex px-5 active } ">
                                                        <span class="symbol symbol-20px me-4">
                                                            <img class="rounded-1" src="/Content/images/flags/vi.svg" alt="Tiếng Việt" />
                                                        </span>Tiếng Việt
                                                    </a>
                                                </div>
                                                <div class="menu-item px-3">
                                                    <a href="javascript:void(0)" id="en" class="menu-link d-flex px-5  } ">
                                                        <span class="symbol symbol-20px me-4">
                                                            <img class="rounded-1" src="/Content/images/flags/en.svg" alt="Tiếng Anh" />
                                                        </span>Tiếng Anh
                                                    </a>
                                                </div>
                                                <div class="menu-item px-3">
                                                    <a href="javascript:void(0)" id="jp" class="menu-link d-flex px-5  } ">
                                                        <span class="symbol symbol-20px me-4">
                                                            <img class="rounded-1" src="/Content/images/flags/jp.svg" alt="Tiếng Nhật" />
                                                        </span>Tiếng Nhật
                                                    </a>
                                                </div>



                                    </div>

                                </div>
                                <div class="app-navbar-item ms-1 ms-lg-3" id="kt_header_user_menu_toggle">
                                    <div class="cursor-pointer symbol symbol-35px symbol-md-40px" data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end">
                                        <div class="symbol-label" style="background-image:url('/Content/upload/users/avatar/02114273.png')"></div>
                                    </div>
                                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-275px" data-kt-menu="true">
                                        <div class="menu-item px-3">
                                            <div class="menu-content d-flex align-items-center px-3">
                                                <div class="symbol-label" style="background-image:url('/Content/upload/users/avatar/02114273.png')"></div>
                                                <div class="d-flex flex-column">
                                                    <div class="fw-bold d-flex align-items-center fs-5">
                                                        Th&#226;n Trọng Tuấn
                                                    </div>
                                                    <a href="#" class="fw-semibold text-muted text-hover-primary fs-7"> Group Leader</a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="separator my-2"></div>

                                        <div class="menu-item px-5">
                                            <a href="javascript:void(0);" class="menu-link px-5" id="myInfo">T&#224;i khoản của t&#244;i</a>
                                        </div>

                                        <div class="separator my-2"></div>


                                        <div class="menu-item px-5">
                                            <a href="/logout" class="menu-link px-5">Đăng xuất</a>
                                        </div>
                                    </div>

                                </div>
                            </div>

                        <div class="app-navbar-item d-lg-none ms-2 me-n3" title="Show header menu">
                            <div class="btn btn-icon btn-active-color-primary w-35px h-35px" id="kt_app_header_menu_toggle">
                                <span class="svg-icon svg-icon-1">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M13 11H3C2.4 11 2 10.6 2 10V9C2 8.4 2.4 8 3 8H13C13.6 8 14 8.4 14 9V10C14 10.6 13.6 11 13 11ZM22 5V4C22 3.4 21.6 3 21 3H3C2.4 3 2 3.4 2 4V5C2 5.6 2.4 6 3 6H21C21.6 6 22 5.6 22 5Z" fill="currentColor" />
                                        <path opacity="0.3" d="M21 16H3C2.4 16 2 15.6 2 15V14C2 13.4 2.4 13 3 13H21C21.6 13 22 13.4 22 14V15C22 15.6 21.6 16 21 16ZM14 20V19C14 18.4 13.6 18 13 18H3C2.4 18 2 18.4 2 19V20C2 20.6 2.4 21 3 21H13C13.6 21 14 20.6 14 20Z" fill="currentColor" />
                                    </svg>
                                </span>
                            </div>
                        </div>
                    </div>


                </div>

            </div>
        </div>
        <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
            <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
                <div class="d-flex flex-column flex-column-fluid">
                    
    <!--begin::Subheader-->
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack gap-3">
            <!--begin::Info-->
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">Tan ca</h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">Duyệt tăng ca thực tế</li>
                </ul>
            </div>
            <!--end::Info-->
            <!--begin::Toolbar-->
            <div class="d-flex align-items-center gap-2 gap-lg-3 bsc-subheader-actions">
                <button type="button" class="btn btn-primary btn-sm btn-success me-2" id="iDuyet"  >
                    <span class="indicator-label"><i class="fa-duotone fa-circle-check"></i> <span id="lbiDuyet">Duyệt</span></span>
                    <span class="indicator-progress">
                        <span class="spinner-border spinner-border-sm align-middle ms-3"></span> Đang xử lý..
                    </span>
                </button>
                <button type="button" class="btn btn-primary btn-sm btn-danger me-2" id="iTuChoi"  >
                    <span class="indicator-label"><i class="fa-duotone fa-circle-xmark"></i> <span id="lbiTuChoi">Từ chối</span></span>
                    <span class="indicator-progress">
                        <span class="spinner-border spinner-border-sm align-middle ms-3"></span> Đang xử lý..
                    </span>
                </button>
                <button type="button" class="btn btn-primary btn-sm btn-primary me-2" id="iXuatEX"  >
                    <span class="indicator-label"><i class="fa-duotone fa-file-excel"></i> <span id="lbiXuatEX">Xuất excel</span></span>
                    <span class="indicator-progress">
                        <span class="spinner-border spinner-border-sm align-middle ms-3"></span> Đang xử lý..
                    </span>
                </button>
            </div>
            <!--end::Toolbar-->
        </div>
    </div>
    <!--end::Subheader-->

                    <div id="kt_app_content" class="app-content flex-column-fluid">
                        <div id="kt_app_content_container" class="app-container container-fluid">
                            



<div class="row">
    <div class="col-lg-12">
        <div class="card card-custom card-stretch gutter-b">
            <div class="card-header">
                <div class="card-title">
                    
                </div>
                <div class="card-toolbar bg-light-primary">
                    <ul class="nav nav-tabs nav-bold nav-tabs-line">
                        <li class="nav-item">
                            <label class="col-lg-12" style="padding-top: 10%;">Từ ng&#224;y:</label>
                        </li>
                        <li class="nav-item">
                            <div class="col-lg-12" id="id_TuNgay_S">
                            </div>
                        </li>
                        <li class="nav-item">
                            <label class="col-lg-12" style="padding-top: 10%">Đến ng&#224;y:</label>
                        </li>
                        <li class="nav-item">
                            <div class="col-lg-12" id="id_DenNgay_S">
                            </div>
                        </li>
                        <li class="nav-item">
                            <label class="col-lg-12" style="padding-top: 10%">Trạng th&#225;i:</label>
                        </li>
                        <li class="nav-item">
                            <div class="col-lg-12" id="id_TrangThai_S">
                            </div>
                        </li>
                        <li class="nav-item ml-5 mr-5">
                            <button type="button" class="btn btn-primary btn-sm btn-primary" id="iFilterDanhSach" onclick="LoadDanhSach()" >
                    <span class="indicator-label"><i class="fa-duotone fa-magnifying-glass"></i> <span id="lbiFilterDanhSach">Lọc</span></span>
                    <span class="indicator-progress">
                        <span class="spinner-border spinner-border-sm align-middle ms-3"></span> Đang xử lý..
                    </span>
                </button>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="card-body d-flex flex-column">
                <div class="row">
                    <div class="col-lg-12">
                        <div id="iDanhSach" class="bsc-grid bsc-grid-header-center  bsc-grid-hideheader">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="mod_TuChoi" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-bs-keyboard="false" data-bs-backdrop="static">
    <div class="modal-dialog modal-sticky-lg" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <div class="swal2-header">
                    <h2 class="swal2-title" id="swal2-title" style="display: flex;">Bạn c&#243; chắc muốn từ chối?</h2>
                </div>
                <div class="swal2-content">
                    <div class="text-danger" id="swal2-content" style="display: block;">Từ chối</div>
                    <input id="iLyDoTuChoi" autocapitalize="off" class="swal2-input" style="display: flex;" placeholder="Nhập L&#253; do từ chối" type="text">
                </div>
                <div class="swal2-actions mt-5">
                    <button type="button" class="btn btn-primary btn-sm btn-light mr-4 pr-xl-4" id="iCancel" onclick="jQuery(this).closest('.modal').modal('hide')" >
                    <span class="indicator-label"><i class="fa-duotone fa-xmark"></i> <span id="lbiCancel">Hủy</span></span>
                    <span class="indicator-progress">
                        <span class="spinner-border spinner-border-sm align-middle ms-3"></span> Đang xử lý..
                    </span>
                </button>
                    <button type="button" class="btn btn-primary btn-sm btn-success" id="iDongY_TuChoi"  >
                    <span class="indicator-label"><i class="fa-duotone fa-check"></i> <span id="lbiDongY_TuChoi">Đồng ý</span></span>
                    <span class="indicator-progress">
                        <span class="spinner-border spinner-border-sm align-middle ms-3"></span> Đang xử lý..
                    </span>
                </button>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="mod_Duyet" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-bs-keyboard="false" data-bs-backdrop="static">
    <div class="modal-dialog modal-sticky-lg" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <div class="swal2-header">
                    <h2 class="swal2-title" id="swal2-title" style="display: flex;">Bạn c&#243; chắc muốn ph&#234; duyệt?</h2>
                </div>
                <div class="swal2-content">
                    <div class="text-success" id="swal2-content" style="display: block;">Duyệt</div>
                </div>
                <div class="swal2-actions mt-5">
                    <button type="button" class="btn btn-primary btn-sm btn-light mr-4 pr-xl-4" id="iCancel" onclick="jQuery(this).closest('.modal').modal('hide')" >
                    <span class="indicator-label"><i class="fa-duotone fa-xmark"></i> <span id="lbiCancel">Hủy</span></span>
                    <span class="indicator-progress">
                        <span class="spinner-border spinner-border-sm align-middle ms-3"></span> Đang xử lý..
                    </span>
                </button>
                    <button type="button" class="btn btn-primary btn-sm btn-success" id="iDongY_Duyet"  >
                    <span class="indicator-label"><i class="fa-duotone fa-check"></i> <span id="lbiDongY_Duyet">Đồng ý</span></span>
                    <span class="indicator-progress">
                        <span class="spinner-border spinner-border-sm align-middle ms-3"></span> Đang xử lý..
                    </span>
                </button>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="loadpanel"></div>





                        </div>
                    </div>
                </div>
                <div id="kt_app_footer" class="app-footer">
                    <div class="app-container container-xxl d-flex flex-column flex-md-row flex-center flex-md-stack py-3">
                        <div class="text-dark order-2 order-md-1">
                            <span class="text-muted fw-semibold me-1">2023&copy;</span>
                            <a href="/" target="_blank" class="text-gray-800 text-hover-primary">BSC&#174; Cloud ERP Solutions</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="kt_quick_user" class="offcanvas offcanvas-right p-10">
        <div class="offcanvas-header d-flex align-items-center justify-content-between pb-5">
            <h3 class="font-weight-bold m-0">User Profile</h3>
            <a href="javascript:void(0);" class="btn btn-xs btn-icon btn-light btn-hover-primary" id="kt_quick_user_close">
                <i class="ki ki-close icon-xs text-muted"></i>
            </a>
        </div>
        <div class="offcanvas-content pr-5 mr-n5">
            <div class="d-flex align-items-center mt-5">
                <div class="symbol symbol-100 mr-5">
                    <div class="symbol-label" style="background-image:url('/Content/upload/users/avatar/02114273.png')"></div>
                    <i class="symbol-badge bg-success"></i>
                </div>
                <div class="d-flex flex-column">
                    <a class="font-weight-bold font-size-h5 text-dark-75 text-hover-primary">Th&#226;n Trọng Tuấn</a>
                    <div class="text-muted mt-1">Group Leader</div>
                    <div class="navi mt-2">
                        <a href="/logout" class="btn btn-sm btn-light-primary font-weight-bolder py-2 px-5">Sign Out</a>
                    </div>
                </div>
            </div>
            <div class="separator separator-dashed mt-8 mb-5"></div>
            <div class="navi navi-spacer-x-0 p-0">
                <div class="d-flex align-items-center bg-hover-light-primary">
                    <div class="symbol symbol-45 symbol-light mr-5">
                        <span class="symbol-label bg-light-primary"><i class="far fa-id-badge text-primary"></i></span>
                    </div>
                    <div class="d-flex flex-column flex-grow-1">
                        <a href="/HoSo/HoSo" class="font-weight-bold text-dark-75 text-hover-primary font-size-lg mb-1">
                            Hồ sơ
                        </a>
                    </div>
                </div>
                <div class="d-flex align-items-center bg-hover-light-primary">
                    <div class="symbol symbol-45 symbol-light mr-5">
                        <span class="symbol-label bg-light-primary"><i class="far fa-address-card text-primary"></i></span>
                    </div>
                    <div class="d-flex flex-column flex-grow-1">
                        <a href="/Portal/HoSo/TheNhanVien" class="font-weight-bold text-dark-75 text-hover-primary font-size-lg mb-1">
                            Thẻ nh&#226;n vi&#234;n
                        </a>
                    </div>
                </div>
                <div class="d-flex align-items-center bg-hover-light-primary">
                    <div class="symbol symbol-45 symbol-light mr-5">
                        <span class="symbol-label bg-light-primary"><i class="fas fa-file-download text-primary"></i></span>
                    </div>
                    <div class="d-flex flex-column flex-grow-1">
                        <a href="javascript:void(0);" class="font-weight-bold text-dark-75 text-hover-primary font-size-lg mb-1" onclick="TaiAnh()">
                            Tải ảnh
                        </a>
                    </div>
                </div>
                <div class="d-flex align-items-center bg-hover-light-primary">
                    <div class="symbol symbol-45 symbol-light mr-5">
                        <span class="symbol-label bg-light-primary"><i class="fas fa-key text-primary"></i></span>
                    </div>
                    <div class="d-flex flex-column flex-grow-1">
                        <a href="javascript:void(0);" class="font-weight-bold text-dark-75 text-hover-primary font-size-lg mb-1" onclick="DoiMatKhau()">
                            Đổi mật khẩu
                        </a>
                    </div>
                </div>
            </div>
            <div class="separator separator-dashed my-7"></div>
        </div>
    </div>
    <div id="mdDoiMatKhauLayout" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="layoutDoiMatKhauTitle" aria-hidden="true" data-bs-keyboard="false" data-bs-backdrop="static">
        <div class="modal-dialog" role="document">
            <div class="modal-content" style="max-height: 80%">
                <div class="modal-header p-2">
                    <h4 class="modal-title pt-2" id="layoutDoiMatKhauTitle">
                        <i class="flaticon-lock icon-2x text-primary"></i>
                        <span id="iFormLabel_UpdateMatKhauLayout"></span>
                    </h4>
                    <div class="kt-portlet__head-toolbar">
                         <a href="javascript: void(0);" id="iClose" tabindex="1000" class="btn btn-light-primary btn-sm me-2"  data-bs-dismiss="modal" aria-label="Close"><i class="fa-duotone fa-arrow-left-long"></i> Quay lại</a>
                        <button type="button" class="btn btn-primary btn-sm bsc-btn-hide" id="iUpdateMatKhauLayOut">
                            <i class="flaticon2-checkmark"></i>
                            <span>Cập nhật</span>
                        </button>
                    </div>
                </div>
                <div class="modal-body pb-1 kt-scroll ps" data-scroll="true">
                    <div class="kt-portlet__body">
                        <div class="kt-portlet kt-portlet--mobile">
                            <div class="kt-portlet__body p-3">
                                <div class="logo" style="text-align:center!important">
                                    <span class="db"><img src="/Content/images/logo.svg" alt="BSC&#174; Cloud ERP Solutions" width="120" /></span>
                                    <h3 class="mt-2" style="margin-bottom:30px;">SMC Manufacturing (Việt Nam)</h3>
                                    <span style="font-size: 14px; color: red; display: block;">Lưu &#253;: Mật khẩu phải c&#243; &#237;t nhất 8 k&#253; tự, phải c&#243; 1 chữ số, 1 chữ viết hoa, 1 chữ viết thường, 1 k&#253; tự đặc biệt</span>
                                </div>
                                <div class="form-group row mb-2">
                                    <div class="col-lg-12">
                                        <label class="col-form-label col-form-label-sm">Số T&#224;i Khoản ng&#226;n h&#224;ng</label>
                                        <input type="text" id="layout_Username" class="form-control form-control-lg" disabled="disabled" value="02114273" autocomplete="off" />
                                    </div>
                                    <input type="text" style="display:none">
                                    <input type="password" style="display:none">
                                    <div class="col-lg-12">
                                        <label class="col-form-label col-form-label-sm">Mật khẩu cũ</label>
                                        <input type="password" class="form-control" id="layout_MatKhauOLD" placeholder="Nhập Mật khẩu cũ" autocomplete="new-password" required />
                                    </div>
                                    <div class="col-lg-12">
                                        <label class="col-form-label col-form-label-sm">Mật khẩu mới</label>
                                        <input type="password" class="form-control" id="layout_MatKhauMoi" placeholder="Nhập Mật khẩu mới" autocomplete="new-password" required />
                                    </div>
                                    <div class="col-lg-12">
                                        <label class="col-form-label col-form-label-sm">X&#225;c nhận mật khẩu</label>
                                        <input type="password" class="form-control" id="layout_XacNhanMatKhau" placeholder="Nhập X&#225;c nhận mật khẩu" autocomplete="new-password" required />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="kt_help" class="bg-body" data-kt-drawer="true" data-kt-drawer-name="help" data-kt-drawer-activate="true" data-kt-drawer-overlay="true" data-kt-drawer-width="{default:'350px', 'md': '525px'}" data-kt-drawer-direction="end" data-kt-drawer-toggle="#kt_help_toggle" data-kt-drawer-close="#kt_help_close">
        <div class="card shadow-none rounded-0 w-100">
            <div class="card-header" id="kt_help_header">
                <h5 class="card-title fw-semibold text-gray-600">T&#236;m kiếm</h5>
                <div class="card-toolbar">
                    <button type="button" class="btn btn-sm btn-icon explore-btn-dismiss me-n5" id="kt_help_close">
                        <span class="svg-icon svg-icon-2">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect opacity="0.5" x="6" y="17.3137" width="16" height="2" rx="1" transform="rotate(-45 6 17.3137)" fill="currentColor" />
                                <rect x="7.41422" y="6" width="16" height="2" rx="1" transform="rotate(45 7.41422 6)" fill="currentColor" />
                            </svg>
                        </span>
                    </button>
                </div>
            </div>
            <div class="card-body" id="kt_help_body">
                <div id="kt_help_scroll" class="hover-scroll-overlay-y" data-kt-scroll="true" data-kt-scroll-height="auto" data-kt-scroll-wrappers="#kt_help_body" data-kt-scroll-dependencies="#kt_help_header" data-kt-scroll-offset="5px">
                    
                </div>
            </div>
        </div>
    </div>
    <div id="iDataGrid" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true" data-keyboard="false" data-backdrop="static">
    <div class="modal-dialog modal-xl  bsc-modal-full">
        <div class="modal-content">
            <div class="modal-body bsc-data-modal-body">
                <iframe style="width:100%;height:100%;border:none" frameborder="0" id="iFDataGrid"></iframe>
            </div>

        </div>
    </div>
</div>
<div id="iDataGrid2" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true" data-keyboard="false" data-backdrop="static">
    <div class="modal-dialog modal-xl  bsc-modal-full">
        <div class="modal-content">
            <div class="modal-body bsc-data-modal-body">
                <iframe style="width:100%;height:100%;border:none" frameborder="0" id="iFDataGrid2"></iframe>
            </div>

        </div>
    </div>
</div>
    
<style>
    #newModule .module-hub-dialog {
        max-width: 1140px;
        margin-top: .8rem;
        margin-bottom: .8rem;
    }

    #newModule .modal-content {
        border: 0;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 26px 58px rgba(10, 31, 68, .28);
    }

    #newModule .modal-header {
        padding: .66rem 1.05rem;
        border-bottom: 1px solid #e8edf5;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .module-hub-title {
        margin: 0;
        font-size: 1.55rem;
        font-weight: 700;
        color: #1b2338;
        display: inline-flex;
        align-items: center;
        gap: .46rem;
    }

    .module-hub-title i {
        color: #4891ff;
        font-size: 1rem;
    }

    .module-hub-back {
        border: 0;
        background: transparent;
        color: #2f3b58;
        font-size: 1.06rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .34rem .6rem;
        border-radius: 10px;
        transition: background-color .18s ease, color .18s ease;
    }

    .module-hub-back:hover {
        background: #f2f6ff;
        color: #15428d;
    }

    .module-hub-body {
        padding: 0;
        min-height: 70vh;
        max-height: calc(100vh - 155px);
        overflow: auto;
        background: linear-gradient(180deg, #9ac6df 0%, #edf2f8 48%, #ebf1f7 100%);
    }

    .module-hub-shell {
        padding: 1.35rem;
    }

    .module-hub-panel {
        max-width: 1000px;
        margin: 0 auto;
        border-radius: 24px;
        border: 1px solid #e5ebf6;
        background: #ffffff;
        box-shadow: 0 16px 36px rgba(16, 36, 79, .10);
        padding: 1.35rem 1.35rem 1.45rem;
    }

    .module-hub-panel-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: .75rem;
        margin-bottom: 1.12rem;
    }

    .module-hub-heading {
        margin: 0;
        color: #f59e0b;
        font-size: 2.02rem;
        font-weight: 700;
        line-height: 1.15;
    }

    .module-hub-subtitle {
        margin-top: .34rem;
        color: #5e6f8e;
        font-size: 1.1rem;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: .45rem;
    }

    .module-hub-subtitle i {
        color: #7d8aa2;
        font-size: 1.04rem;
    }

    .module-hub-count {
        margin-top: .28rem;
        color: #0f1f3d;
        font-size: 1.1rem;
        font-weight: 500;
        white-space: nowrap;
    }

    .module-hub-count b {
        font-size: 1.32em;
        color: #1c67e3;
        font-weight: 800;
    }

    .module-hub-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: .9rem;
    }

    .module-hub-item {
        position: relative;
        overflow: hidden;
        border-radius: 18px;
        border: 1px solid #e5ebf6;
        background: #ffffff;
        padding: .88rem .88rem .88rem .98rem;
        display: flex;
        align-items: center;
        gap: .85rem;
        min-height: 112px;
        text-decoration: none;
        box-shadow: 0 11px 24px rgba(16, 36, 79, .08);
        transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
    }

    .module-hub-item::before {
        content: "";
        position: absolute;
        left: 0;
        top: 14px;
        bottom: 14px;
        width: 4px;
        border-radius: 0 8px 8px 0;
        background: var(--hub-accent);
    }

    .module-hub-item:hover {
        transform: translateY(-2px);
        border-color: color-mix(in srgb, var(--hub-accent) 35%, #d5e0f1 65%);
        box-shadow: 0 16px 30px rgba(16, 36, 79, .14);
    }

    .module-hub-icon {
        width: 64px;
        height: 64px;
        border-radius: 16px;
        background: linear-gradient(180deg, #ffffff 0%, #f7faff 100%);
        border: 1px solid #dbe5f4;
        box-shadow: 0 6px 14px rgba(17, 42, 86, .08);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
    }

    .module-hub-icon img {
        width: 38px;
        height: 38px;
        object-fit: contain;
    }

    .module-hub-meta {
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: .12rem;
        flex: 1 1 auto;
    }

    .module-hub-name {
        color: #1b2a45;
        color: color-mix(in srgb, var(--hub-accent) 40%, #0f1f3d 60%);
        font-size: 1.08rem;
        font-weight: 700;
        line-height: 1.32;
        letter-spacing: .01em;
    }

    .module-hub-item:hover .module-hub-name {
        color: color-mix(in srgb, var(--hub-accent) 62%, #0f1f3d 38%);
    }

    .module-hub-go {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        border: 1px solid #e7ecf7;
        background: #f7f9fe;
        color: #6f7d96;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        transition: all .2s ease;
    }

    .module-hub-go i {
        font-size: .86rem;
    }

    .module-hub-item:hover .module-hub-go {
        color: var(--hub-accent);
        border-color: color-mix(in srgb, var(--hub-accent) 32%, #d9e4f3 68%);
        background: color-mix(in srgb, var(--hub-accent) 10%, #f7f9fe 90%);
    }

    .module-hub-detail {
        margin-top: 1rem;
        border: 1px solid #e5ebf6;
        border-radius: 16px;
        background: linear-gradient(180deg, #ffffff 0%, #f9fbff 100%);
        box-shadow: 0 9px 22px rgba(16, 36, 79, .08);
        padding: .95rem 1rem 1rem;
    }

    .module-hub-detail-head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .5rem .75rem;
        margin-bottom: .56rem;
    }

    .module-hub-detail-label {
        color: #7083a3;
        font-size: .92rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .module-hub-detail-name {
        color: #1a2a4a;
        font-size: 1.24rem;
        font-weight: 700;
    }

    .module-hub-detail-content {
        color: #32456a;
        font-size: 1rem;
        line-height: 1.6;
    }

    .module-hub-detail-content p {
        margin-bottom: .42rem;
    }

    .module-hub-detail-empty {
        margin: 0;
        color: #8a97af;
        font-style: italic;
    }

    @media (max-width: 1199.98px) {
        .module-hub-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .module-hub-heading {
            font-size: 2rem;
        }
    }

    @media (max-width: 991.98px) {
        .module-hub-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .module-hub-heading {
            font-size: 1.58rem;
        }
    }

    @media (max-width: 575.98px) {
        #newModule .module-hub-dialog {
            margin: .55rem;
        }

        .module-hub-shell {
            padding: .7rem;
        }

        .module-hub-panel {
            padding: .9rem;
            border-radius: 16px;
        }

        .module-hub-panel-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .module-hub-grid {
            grid-template-columns: 1fr;
            gap: .68rem;
        }

        .module-hub-name {
            font-size: 1.03rem;
        }

        .module-hub-detail-name {
            font-size: 1.08rem;
        }
    }
</style>
<div id="newModule" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-keyboard="false" data-backdrop="static">
    <div class="modal-dialog modal-xl module-hub-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title module-hub-title">
                    <i class="fas fa-compass"></i>
                    <span>Trung t&#226;m điều hướng</span>
                </h4>
                <button type="button" class="module-hub-back" onclick="QuayLai('#newModule')">
                    <i class="fas fa-arrow-left"></i>
                    <span>Quay lại</span>
                </button>
            </div>
            <div class="modal-body module-hub-body">
                <div class="module-hub-shell">
                    <div class="module-hub-panel">
                        <div class="module-hub-panel-header">
                            <div>
                                <h3 class="module-hub-heading">Danh s&#225;ch ph&#226;n hệ</h3>
                                <div class="module-hub-subtitle">
                                    <i class="fas fa-sliders-h"></i>
                                    <span>Di chuột v&#224;o ph&#226;n hệ để xem m&#244; tả</span>
                                </div>
                            </div>
                            <div class="module-hub-count">Tổng <b>7</b> ph&#226;n hệ</div>
                        </div>
                        <div class="module-hub-grid">

                                <a href="javascript:void(0);" class="module-hub-item" style="--hub-accent:#e91e63;" onclick="goModule('/')" data-module-name="Nh&#226;n sự">
                                    <span class="module-hub-icon">
                                        <img src="/Content/images/module/Portal.png" alt="Nh&#226;n sự" />
                                    </span>
                                    <span class="module-hub-meta">
                                        <span class="module-hub-name">Nh&#226;n sự</span>
                                    </span>
                                    <span class="module-hub-go">
                                        <i class="fas fa-chevron-right"></i>
                                    </span>
                                    <span class="d-none module-hub-detail-source">
<b>Nhân sự (HR)</b><br/>
- Hồ sơ nhân viên<br/>
- Chấm công, ca làm việc<br/>
- Nghỉ phép, tăng ca<br/>
- Hợp đồng lao động<br/>
- Báo cáo nhân sự</span>
                                </a>
                                <a href="javascript:void(0);" class="module-hub-item" style="--hub-accent:#29b36b;" onclick="goModule('dao-tao')" data-module-name="Đ&#224;o tạo">
                                    <span class="module-hub-icon">
                                        <img src="/Content/images/module/DaoTao.png" alt="Đ&#224;o tạo" />
                                    </span>
                                    <span class="module-hub-meta">
                                        <span class="module-hub-name">Đ&#224;o tạo</span>
                                    </span>
                                    <span class="module-hub-go">
                                        <i class="fas fa-chevron-right"></i>
                                    </span>
                                    <span class="d-none module-hub-detail-source">
<b>Đào tạo</b><br/>
- Quản lý khóa đào tạo<br/>
- Đăng ký đào tạo<br/>
- Kết quả học tập<br/>
- Đánh giá sau đào tạo<br/>
- Quản lý chứng chỉ</span>
                                </a>
                                <a href="javascript:void(0);" class="module-hub-item" style="--hub-accent:#ffb020;" onclick="goModule('danh-gia/nhan-vien')" data-module-name="&#208;&#225;nh gi&#225;">
                                    <span class="module-hub-icon">
                                        <img src="/Content/images/module/Evaluate.png" alt="&#208;&#225;nh gi&#225;" />
                                    </span>
                                    <span class="module-hub-meta">
                                        <span class="module-hub-name">&#208;&#225;nh gi&#225;</span>
                                    </span>
                                    <span class="module-hub-go">
                                        <i class="fas fa-chevron-right"></i>
                                    </span>
                                    <span class="d-none module-hub-detail-source">
<b>Đánh giá</b><br/>
- Đánh giá KPI<br/>
- Đánh giá năng lực<br/>
- Đánh giá định kỳ<br/>
- Kết quả đánh giá<br/>
- Phân tích hiệu suất</span>
                                </a>
                                <a href="javascript:void(0);" class="module-hub-item" style="--hub-accent:#2196f3;" onclick="goModule('ra-vao-cong/dang-ky')" data-module-name="Tổng vụ">
                                    <span class="module-hub-icon">
                                        <img src="/Content/images/module/TongVu.png" alt="Tổng vụ" />
                                    </span>
                                    <span class="module-hub-meta">
                                        <span class="module-hub-name">Tổng vụ</span>
                                    </span>
                                    <span class="module-hub-go">
                                        <i class="fas fa-chevron-right"></i>
                                    </span>
                                    <span class="d-none module-hub-detail-source">
<b>Tổng vụ</b><br/>
- Quản lý tài sản<br/>
- Quản lý xe công ty<br/>
- Văn phòng phẩm<br/>
- Quản lý phòng họp<br/>
- Yêu cầu dịch vụ nội bộ</span>
                                </a>
                                <a href="javascript:void(0);" class="module-hub-item" style="--hub-accent:#2d6cdf;" onclick="goModule('ringi/list-of-ringi')" data-module-name="Ringi">
                                    <span class="module-hub-icon">
                                        <img src="/Content/images/module/Ringi.png" alt="Ringi" />
                                    </span>
                                    <span class="module-hub-meta">
                                        <span class="module-hub-name">Ringi</span>
                                    </span>
                                    <span class="module-hub-go">
                                        <i class="fas fa-chevron-right"></i>
                                    </span>
                                    <span class="d-none module-hub-detail-source">
<b>Ringi</b><br/>
- Đề xuất phê duyệt<br/>
- Quản lý nhà cung cấp<br/>
- Mua sắm vật tư<br/>
- Luồng phê duyệt điện tử<br/>
- Theo dõi trạng thái xử lý</span>
                                </a>
                                <a href="javascript:void(0);" class="module-hub-item" style="--hub-accent:#8b5cf6;" onclick="goModule('pyt/dang-ky')" data-module-name="An to&#224;n">
                                    <span class="module-hub-icon">
                                        <img src="/Content/images/module/Evaluate6S.png" alt="An to&#224;n" />
                                    </span>
                                    <span class="module-hub-meta">
                                        <span class="module-hub-name">An to&#224;n</span>
                                    </span>
                                    <span class="module-hub-go">
                                        <i class="fas fa-chevron-right"></i>
                                    </span>
                                    <span class="d-none module-hub-detail-source">
<b>An toàn & Sức khỏe</b><br/>
- Kiểm tra an toàn lao động<br/>
- Đánh giá 5S/6S<br/>
- Quản lý sự cố<br/>
- Theo dõi khắc phục<br/>
- Báo cáo môi trường làm việc</span>
                                </a>
                                <a href="javascript:void(0);" class="module-hub-item" style="--hub-accent:#14b8a6;" onclick="goModule('KhaoSat/dash-board')" data-module-name="Khảo s&#225;t">
                                    <span class="module-hub-icon">
                                        <img src="/Content/images/module/KhaoSat.png" alt="Khảo s&#225;t" />
                                    </span>
                                    <span class="module-hub-meta">
                                        <span class="module-hub-name">Khảo s&#225;t</span>
                                    </span>
                                    <span class="module-hub-go">
                                        <i class="fas fa-chevron-right"></i>
                                    </span>
                                    <span class="d-none module-hub-detail-source">
<b>Khảo sát</b><br/>
- Tạo khảo sát<br/>
- Khảo sát nội bộ<br/>
- Thu thập phản hồi<br/>
- Thống kê kết quả<br/>
- Phân tích dữ liệu</span>
                                </a>
                        </div>

                        <div class="module-hub-detail" id="moduleHubDetail">
                            <div class="module-hub-detail-head">
                                <span class="module-hub-detail-label">M&#244; tả ph&#226;n hệ</span>
                                <span class="module-hub-detail-name" id="moduleHubDetailName">Nh&#226;n sự</span>
                            </div>
                            <div class="module-hub-detail-content" id="moduleHubDetailBody">
                                
<b>Nhân sự (HR)</b><br/>
- Hồ sơ nhân viên<br/>
- Chấm công, ca làm việc<br/>
- Nghỉ phép, tăng ca<br/>
- Hợp đồng lao động<br/>
- Báo cáo nhân sự
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="bscmodule"></div>



<script type="text/javascript">
    var moduleHubDefaultName = 'Phân hệ';
    var moduleHubEmptyDetailHtml = '\u003cp class=\u0027module-hub-detail-empty\u0027\u003eChưa có mô tả cho phân hệ này.\u003c/p\u003e';

    function QuayLai(id) {
        $(id).modal('hide');
    }

    (function () {
        var bindModuleDetailHover = function () {
            var modal = document.getElementById('newModule');
            var detailName = document.getElementById('moduleHubDetailName');
            var detailBody = document.getElementById('moduleHubDetailBody');
            if (!modal || !detailName || !detailBody) {
                return;
            }

            var items = modal.querySelectorAll('.module-hub-item');
            if (!items || items.length === 0) {
                return;
            }

            var showDetail = function (item) {
                if (!item) {
                    return;
                }
                var moduleName = item.getAttribute('data-module-name') || moduleHubDefaultName;
                var detailSource = item.querySelector('.module-hub-detail-source');
                var detailHtml = detailSource ? detailSource.innerHTML : moduleHubEmptyDetailHtml;
                detailName.textContent = moduleName;
                detailBody.innerHTML = detailHtml;
            };

            Array.prototype.forEach.call(items, function (item) {
                item.addEventListener('mouseenter', function () {
                    showDetail(item);
                });
                item.addEventListener('focus', function () {
                    showDetail(item);
                });
            });
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bindModuleDetailHover);
            return;
        }
        bindModuleDetailHover();
    })();
</script>

    <script>
        var KTAppSettings = {
            "breakpoints": {
                "sm": 576,
                "md": 768,
                "lg": 992,
                "xl": 1200,
                "xxl": 1400
            },
            "colors": {
                "theme": {
                    "base": {
                        "white": "#ffffff",
                        "primary": "#3699FF",
                        "secondary": "#E5EAEE",
                        "success": "#1BC5BD",
                        "info": "#8950FC",
                        "warning": "#FFA800",
                        "danger": "#F64E60",
                        "light": "#E4E6EF",
                        "dark": "#181C32"
                    },
                    "light": {
                        "white": "#ffffff",
                        "primary": "#E1F0FF",
                        "secondary": "#EBEDF3",
                        "success": "#C9F7F5",
                        "info": "#EEE5FF",
                        "warning": "#FFF4DE",
                        "danger": "#FFE2E5",
                        "light": "#F3F6F9",
                        "dark": "#D6D6E0"
                    },
                    "inverse": {
                        "white": "#ffffff",
                        "primary": "#ffffff",
                        "secondary": "#3F4254",
                        "success": "#ffffff",
                        "info": "#ffffff",
                        "warning": "#ffffff",
                        "danger": "#ffffff",
                        "light": "#464E5F",
                        "dark": "#ffffff"
                    }
                },
                "gray": {
                    "gray-100": "#F3F6F9",
                    "gray-200": "#EBEDF3",
                    "gray-300": "#E4E6EF",
                    "gray-400": "#D1D3E0",
                    "gray-500": "#B5B5C3",
                    "gray-600": "#7E8299",
                    "gray-700": "#5E6278",
                    "gray-800": "#3F4254",
                    "gray-900": "#181C32"
                }
            },
            "font-family": "Poppins"
        };
    </script>
    <style>
    #mdKeHoachKhaoSat .form-buttons, #mdKhaoSat .form-buttons, #mdKhaoSatHoanThanh .form-buttons {
        display: inline-block;
        margin-top: 0px;
    }

    .kt-grid__item--fluid {
        padding-top: 10px;
    }

    .kt-widget14__bullet {
        width: 1.5rem;
        height: .45rem;
        border-radius: 1.1rem;
    }

    .active-input input {
        width: 33px !important;
    }

    .execution {
        margin: 10px 10px;
        font-size: 110%;
        display: inline;
        /* Double-sized Checkboxes */
        -ms-transform: scale(2); /* IE */
        -moz-transform: scale(2); /* FF */
        -webkit-transform: scale(2); /* Safari and Chrome */
        -o-transform: scale(2); /* Opera */
        transform: scale(2);
        padding: 10px;
    }

    .form-input input:checked::after {
        content: url(../../Content/images/khaosat/check.png) !important;
    }
</style>
<link href="/Content/khaosat/quiz.css" rel="stylesheet" />


<div class="modal fade" id="mdKeHoachKhaoSat" tabindex="-1" aria-hidden="true" data-keyboard="false" data-backdrop="static">
    <!--begin::Modal dialog-->
    <div class="modal-dialog modal-dialog-centered  ">
        <!--begin::Modal content-->
        <div class="modal-content rounded">
            <!--begin::Modal header-->
            <div class="modal-header pb-0 border-0 justify-content-end">
                <!--begin::Close-->
                

                <!--end::Close-->
            </div>
            <!--begin::Modal header-->
            <!--begin::Modal body-->
            <div class="modal-body scroll-y px-10 px-lg-15 pt-0 pb-15">
                <!--begin:Form-->
                <form id="kt_modal_bidding_form" class="form" action="#">
                    <!--begin::Heading-->
                    <div class="mb-13 text-center">
                        <div class="row mb-5">
                            <div class="col-lg-12 text-center">
                                <img id="HinhAnhKeHoach" style="width:200px;height:200px" class="editable img-responsive editable-click editable-empty" alt="" src="/Content/images/noavatar.png">
                            </div>

                        </div>
                        <div class="row">
                            <div class="col-lg-12">
                                <!--begin::Title-->
                                <h3 class="mb-3" id="iTieuDeKH"></h3>
                                <!--end::Title-->
                                <!--begin::Description-->
                                <div class="text-muted fw-semibold fs-5">
                                    <h4 id="iNoiDungKH"></h4>
                                </div>
                                <!--end::Description-->
                            </div>
                        </div>


                    </div>
                    <!--end::Heading-->
                    <!--begin::Input group-->
                    <div class="d-flex flex-column mb-8 fv-row">
                    </div>

                    <!--end::Actions-->
                </form>
                <!--end:Form-->
            </div>

            <div class="modal-footer p-0">
                <div class="form-buttons mt-3 mb-3">
                    <button type="button" onclick="BoQua()" id="id_BoQua" class="prev bg-primary"><i class="flaticon-stopwatch fs-3 me-1"></i>Bỏ qua</button>
                    <button type="button" onclick="BatDau()" class="next">Bắt đầu<i class="flaticon2-fast-next fs-3 me-1"></i></button>
                </div>

            </div>
            <!--end::Modal body-->
        </div>
        <!--end::Modal content-->
    </div>
    <!--end::Modal dialog-->
</div>

<div class="modal fade" id="mdKhaoSat" tabindex="-1" aria-hidden="true" data-keyboard="false" data-backdrop="static">
    <!--begin::Modal dialog-->
    <div class="modal-dialog modal-dialog-centered  modal-dialog-scrollable">
        <!--begin::Modal content-->
        <div class="modal-content rounded">
            <!--begin::Modal header-->
            <div class="modal-header pb-0 border-0 justify-content-end">
                <!--begin::Close-->
                

                <!--end::Close-->
            </div>
            <!--begin::Modal header-->
            <!--begin::Modal body-->
            <div class="modal-body scroll-y px-10 px-lg-15 pt-0 pb-15">
                <!--begin:Form-->
                <form id="kt_modal_bidding_form" class="form" action="#">
                    <!--begin::Input group-->
                    <div class="d-flex flex-column mb-8 fv-row">
                        <div id="iKhaoSat" style="margin-top:20px">

                        </div>
                    </div>
                    <!--end::Actions-->
                </form>
                <!--end:Form-->
            </div>

            <div class="modal-footer p-0">

                <div class="w-100" id="id_btn_KhaoSat">

                </div>

            </div>
            <!--end::Modal body-->
        </div>
        <!--end::Modal content-->
    </div>
    <!--end::Modal dialog-->
</div>

<div class="modal fade" id="mdKhaoSatHoanThanh" tabindex="-1" aria-hidden="true" data-keyboard="false" data-backdrop="static">
    <!--begin::Modal dialog-->
    <div class="modal-dialog modal-dialog-centered  modal-dialog-scrollable">
        <!--begin::Modal content-->
        <div class="modal-content rounded">
            <!--begin::Modal header-->
            <div class="modal-header pb-0 border-0 justify-content-end">
                <!--begin::Close-->
                

                <!--end::Close-->
            </div>
            <!--begin::Modal header-->
            <!--begin::Modal body-->
            <div class="modal-body scroll-y px-10 px-lg-15 pt-0 pb-15">
                <!--begin:Form-->
                <form id="kt_modal_bidding_form" class="form" action="#">
                    <!--begin::Heading-->
                    <div class="mb-13 text-center">
                        <div class="row mb-5">
                            <div class="col-lg-12">
                                <img id="HinhAnhHoanThanhKeHoach" style="width:200px;height:200px" class="editable img-responsive editable-click editable-empty" alt="" src="/Content/images/noavatar.png">
                            </div>

                        </div>
                        <div class="row">
                            <div class="col-lg-12">
                                <!--begin::Title-->
                                <h3 class="mb-3" id="iTieuDeHoanThanhKH"></h3>
                                <!--end::Title-->
                                <!--begin::Description-->
                                <div class="text-muted fw-semibold fs-5">
                                    <h4 id="iNoiDungHoanThanhKH"></h4>
                                </div>
                                <!--end::Description-->
                            </div>
                        </div>


                    </div>

                    <!--begin::Input group-->
                    <div class="d-flex flex-column mb-8 fv-row">
                        <div id="iKhaoSatHoanThanh" style="margin-top:20px" class="clsKhaoSatHoanThanh">

                        </div>
                    </div>

                    <!--end::Actions-->
                </form>
                <!--end:Form-->
            </div>
            <div class="modal-footer p-0">
                <div class="form-buttons mt-3 mb-3">
                    <button type="button" id="id_ThayDoi" onclick="ThayDoi()" class="prev bg-primary"><i class="flaticon-edit-1 fs-3 me-1"></i>Thay đổi</button>
                    <button type="button" id="id_KetThucKhaoSat" onclick="KetThucKhaoSat()" class="next bg-success">Ho&#224;n th&#224;nh<i class="flaticon2-checkmark fs-3 me-1"></i></button>
                </div>
            </div>
            <!--end::Modal body-->
        </div>
        <!--end::Modal content-->
    </div>
    <!--end::Modal dialog-->
</div>

<div id="iMau" style="display:none!important">
    <table style="width:100%;margin:5px;background-color:#fff;border-bottom:1px solid rgba(0,0,0,.125);">
        <tr>
            <td>
                <span class="btn-active form-heading">repTieuDe</span>
            </td>
        </tr>
        <tr>
            <td class="pb-2">
                repDapAn
            </td>

        </tr>
    </table>
</div>
<script>
    var KTAppSettings = {
        "breakpoints": {
            "sm": 576,
            "md": 768,
            "lg": 992,
            "xl": 1200,
            "xxl": 1400
        },
        "colors": {
            "theme": {
                "base": {
                    "white": "#ffffff",
                    "primary": "#3699FF",
                    "secondary": "#E5EAEE",
                    "success": "#1BC5BD",
                    "info": "#8950FC",
                    "warning": "#FFA800",
                    "danger": "#F64E60",
                    "light": "#E4E6EF",
                    "dark": "#181C32"
                },
                "light": {
                    "white": "#ffffff",
                    "primary": "#E1F0FF",
                    "secondary": "#EBEDF3",
                    "success": "#C9F7F5",
                    "info": "#EEE5FF",
                    "warning": "#FFF4DE",
                    "danger": "#FFE2E5",
                    "light": "#F3F6F9",
                    "dark": "#D6D6E0"
                },
                "inverse": {
                    "white": "#ffffff",
                    "primary": "#ffffff",
                    "secondary": "#3F4254",
                    "success": "#ffffff",
                    "info": "#ffffff",
                    "warning": "#ffffff",
                    "danger": "#ffffff",
                    "light": "#464E5F",
                    "dark": "#ffffff"
                }
            },
            "gray": {
                "gray-100": "#F3F6F9",
                "gray-200": "#EBEDF3",
                "gray-300": "#E4E6EF",
                "gray-400": "#D1D3E0",
                "gray-500": "#B5B5C3",
                "gray-600": "#7E8299",
                "gray-700": "#5E6278",
                "gray-800": "#3F4254",
                "gray-900": "#181C32"
            }
        },
        "font-family": "Poppins"
    };
</script>

<script type="text/javascript">
    function initPopupKhaoSat() {

        $('#iBack').click(function () {
            $('#mdKhaoSat').modal('hide');
            //openModule();
            //location.reload();
        });
        KiemTraKeHoach();
    }

    if (window.jQuery) {
        $(initPopupKhaoSat);
    } else {
        document.addEventListener('DOMContentLoaded', function () {
            if (window.jQuery) {
                $(initPopupKhaoSat);
            }
        });
    }

    function KiemTraKeHoach() {

            $.get('/Home/KiemTraKeHoach').done(function (data) {
                if (data[0] == 0) {
                    $.get('/Home/GetKeHoachKhaoSat').done(function (rs) {
                        console.log(rs);
                        if (rs > 0) {
                            KeHoach = rs;
                            LoadKeHoachKhaoSat(rs);
                            $('#mdKeHoachKhaoSat').modal('show');
                        }
                    })
                }
                else {
                    //openModule();
                }

            })
        }

    function LoadKeHoachKhaoSat(KeHoach) {

            mang = 0, CauHoiDem = 0, CauHoiKeTiepDem = 0, DapAnDem = 0, ID_STT = 0;
            $.post('/Home/GetDanhSachCauHoi', { KeHoach: KeHoach })
                .done(function (data) {
                    if (data.length > 0) {
                        $('#iTieuDeKH').html(data[0].TieuDe);
                        $('#iNoiDungKH').html(data[0].NoiDung);
                        $('#HinhAnhKeHoach').attr('src', data[0].Hinh);
                        if (data[0].TrangThaiBoQua == 1) {
                            $('#id_BoQua').addClass('d-none');
                        }
                        else {
                            $('#id_BoQua').removeClass('d-none');
                        }
                        KeHoach = data;
                    }
                    else {
                    }
                });
        }

        function BatDau() {
            $('#mdKeHoachKhaoSat').modal('hide');
            $('#mdKhaoSat').modal('show');
            LoadKhaoSat(KeHoach);

        }

        function BoQua() {
            $('#mdKeHoachKhaoSat').modal('hide');
            /*openModule();*/
        }

        function LoadKhaoSat(KeHoach) {
            mang = 0, CauHoiDem = 0, CauHoiKeTiepDem = 0, DapAnDem = 0, ID_STT = 0;
            dataDaNhap = [];
            $.post('/Home/GetDanhSachCauHoi', { KeHoach: KeHoach })
                .done(function (data) {
                    if (data.length > 0) {
                        datasource = data;
                        mang = 0;
                        LoadThongTinCauHoi(datasource[mang].CauHoi);
                    }
                    else {
                    }
                });
        }

        function LoadThongTinCauHoi(CauHoi) {
            $('#iKhaoSat').empty();
            $('#id_btn_KhaoSat').empty();
            $.post('/Home/GetKhaoSatCauHoi', { KeHoach: KeHoach, CauHoi: CauHoi })
                .done(function (data) {
                    if (data.length > 0) {
                        $.each(data, function (i, v) {
                            var pt = (((v.STT) / datasource.length) * 100).toFixed(0);

                            var html = `<section class="steps-inner"  style=""> <div class="p-3"> <div class="step-bar"> <span class="step-counter"> Câu ` + (v.STT) + ` / ` + datasource.length + ` </span>
                                <div class="step-bar-inner"> <div class="progress">
                                <div class="progress-bar bg-danger mw-`+ pt + `" role="progressbar" aria-valuenow="` + pt + `" aria-valuemin="0" aria-valuemax="100">` + pt + `%</div>
                                </div> </div> </div>
                                <div>
                        <div class="form-heading">
                           ` + (v.STT) + `. ` + v.TenCauHoi + `
                        </div>
                        <div class="form-inner pop-slide" id="pnNoiDung">
                        </div>

                        </section>`;
                            $('#iKhaoSat').html(html);

                            var html_btn = `<div class="form-buttons mt-3 mb-3">
                                            <button type="button" id="QuayLai" onclick="Back()" class="prev"><i class="flaticon2-back"></i>C&#226;u trước</button>
                                            <button type="button" id="KeTiep" onclick="Next()" class="next">C&#226;u tiếp<i class="flaticon2-fast-next"></i></button>
                                            <button type="button" id="HoanThanh" onclick="KetThuc()" class="next bg-success hidden">Ho&#224;n th&#224;nh<i class="flaticon2-checkmark"></i></button>
                                           </div>`;
                            $('#id_btn_KhaoSat').html(html_btn);

                            if (v.KieuCauHoi == 1) {

                                DapAnCauHoiChonMot(v.CauHoi, v.DapAnChon, KeHoach, v.GhiChu, v.STT);
                            }

                            if (v.KieuCauHoi == 4) {

                                DapAnCauHoiChonNhieu(v.CauHoi, v.DapAnChon, KeHoach, v.GhiChu, v.STT);
                            }

                            if (v.KieuCauHoi == 2) {
                                $('#pnNoiDung').html(`<div class="form-group mt-3  pn-ghichu" id="pn_GhiChuLoai2` + v.CauHoi + `"> <label class="form-label" style="font-size: 19px; color: rgb(0, 10, 56);" for="txtGhiChu_` + v.CauHoi + `"></label> <input class="form-control " id="GhiChuLoai2_` + v.CauHoi + `" type="text" placeholder="Nhập nội dung" value="` + v.GhiChu + `"> </div>`);
                            }

                            if (v.KieuCauHoi == 5) {
                                $('#pnNoiDung').html(`<div class="form-group mt-3  pn-ghichu" id="pn_GhiChuLoai5` + v.CauHoi + `"> <label class="form-label" style="font-size: 19px; color: rgb(0, 10, 56);" for="txtGhiChu_` + v.CauHoi + `"></label> <input class="form-control " id="GhiChuLoai5_` + v.CauHoi + `" + ' type="number" placeholder="Nhập số" value="` + v.GhiChu + `"> </div>`);
                            }

                            if (v.KieuCauHoi == 6) {

                                DapAnCauHoiChonHaiLong(v.CauHoi, v.DapAnChon, KeHoach, v.GhiChu, v.STT);
                            }

                            if (v.GhiChu != null) {
                                CauHoiKeTiepDem = -1;
                                DapAnDem = -1;
                                IDCauHoi = CauHoi;
                            }

                            // Click chọn cập nhật dữ liệu


                            $('#GhiChuLoai2_' + v.CauHoi).on("change", function () {
                                CapNhatCauText(v.CauHoi, $(this).val(), KeHoach, 1);
                            });

                            $('#GhiChuLoai5_' + v.CauHoi).on("change", function () {
                                CapNhatCauText(v.CauHoi, $(this).val(), KeHoach, 1);
                            });
                            if (v.STT == 1) {
                                $('#QuayLai').addClass('hidden');
                                $('#KeTiep').removeClass('hidden');
                                $('#HoanThanh').addClass('hidden');
                            }
                            else if (datasource.length - 1 == mang) {
                                $('#QuayLai').removeClass('hidden');
                                $('#KeTiep').addClass('hidden');
                                $('#HoanThanh').removeClass('hidden');
                            }

                        })
                    }
                    else {
                    }
                });
        }

        function DapAnCauHoiChonMot(CauHoi, DapAn, ID, GhiChu, STT) {
            $('#pnNoiDung').empty();
            $.post('/Home/GetDapAn', { CauHoi: CauHoi, KeHoach: KeHoach })
                .done(function (data) {
                    data = JSON.parse(data.ResponseData);
                    if (data.length > 0) {
                        $.each(data, function (i, v) {
                            if (DapAn != null) {
                                if (DapAn == v.DapAn) {
                                    $('#pnNoiDung').append(` <label class="form-input active-input" for="da_` + (v.DapAn) + `"> <input type="radio" checked  dindex="` + i + `" name="qLoai1_` + CauHoi + `" id="da_` + (v.DapAn) + `"> ` + v.TenDapAn + ` </label>`);
                                    CauHoiKeTiepDem = v.CauHoiKeTiep;
                                    DapAnDem = v.DapAn;
                                    if (STT == 1) {
                                        ID_STT = STT;
                                    }
                                    IDCauHoi = CauHoi;
                                } else {
                                    $('#pnNoiDung').append(` <label class="form-input" for="da_` + (v.DapAn) + `"> <input type="radio" dindex="` + i + `" name="qLoai1_` + CauHoi + `" id="da_` + (v.DapAn) + `"> ` + v.TenDapAn + ` </label>`);

                                }

                            }
                            else {
                                $('#pnNoiDung').append(` <label class="form-input" for="da_` + (v.DapAn) + `"> <input type="radio" dindex="` + i + `" name="qLoai1_` + CauHoi + `" id="da_` + (v.DapAn) + `"> ` + v.TenDapAn + ` </label>`);
                                DapAnDem = 0;
                            }


                            //event khi click

                            $('#da_' + v.DapAn).change(function () {

                                CapNhatCauChonMot(CauHoi, v.DapAn, ID, 1, v.CauHoiKeTiep, STT);
                            })

                            $('.form-input input').on("change", function () {
                                $(".form-input").removeClass("active-input");
                                $(this).parent().addClass("active-input");

                            });


                        })
                    }
                    else {
                    }
                });
        }


        function DapAnCauHoiChonNhieu(CauHoi, DapAn, ID, GhiChu, STT) {
            $('#pnNoiDung').empty();
            $.post('/Home/GetDapAn', { CauHoi: CauHoi, KeHoach: KeHoach })
                .done(function (data) {
                    data = JSON.parse(data.ResponseData);
                    if (data.length > 0) {
                        $.each(data, function (i, v) {
                            $('#pnNoiDung').append(` <label class="form-input" for="da_` + (v.DapAn) + `"> <input type="checkbox" name="dapan" dindex="` + i + `" name="qLoai4_` + CauHoi + `" id="da2_` + (v.DapAn) + `"> ` + v.TenDapAn + ` </label>`);

                            // gán lại kết quả sau khi lưu
                            if (DapAn != null) {
                                if (DapAn.includes(v.DapAn)) {
                                    $('#da2_' + v.DapAn).attr("checked", true);
                                    CauHoiKeTiepDem = v.CauHoiKeTiep;
                                    DapAnDem = v.DapAn;
                                    if (STT == 1) {
                                        ID_STT = STT;
                                    }
                                    IDCauHoi = CauHoi;
                                    $("label[for='da_" + v.DapAn + "']").addClass("active-input");
                                }
                                else {
                                    $('#da2_' + v.DapAn).attr("checked", false);
                                }


                            }
                            else {
                                DapAnDem = 0;
                            }

                            //event khi click

                            $('#da2_' + v.DapAn).change(function () {

                                if ($(this).is(":checked")) {
                                    CapNhatCauChonNhieu(CauHoi, v.DapAn, 1, ID, 1, v.CauHoiKeTiep, STT);
                                }
                                else {
                                    CapNhatCauChonNhieu(CauHoi, v.DapAn, 0, ID, 1, v.CauHoiKeTiep, STT);
                                }
                            })

                            $('.form-input input').on("change", function () {
                                $(".form-input").removeClass("active-input");
                                $(this).parent().addClass("active-input");

                            });

                        })
                    }
                    else {
                    }
                });
        }

        function DapAnCauHoiChonHaiLong(CauHoi, DapAn, ID, GhiChu, STT) {
            $('#pnNoiDung').empty();
            $.post('/Home/GetDapAn', { CauHoi: CauHoi, KeHoach: KeHoach })
                .done(function (data) {
                    data = JSON.parse(data.ResponseData);
                    if (data.length > 0) {
                        $.each(data, function (i, v) {
                            if (DapAn != null) {
                                if (DapAn == v.DapAn) {
                                    if (v.IDSTT == 1) {
                                        $('#pnNoiDung').append(` <label class="form-input active-input" for="da_` + (v.DapAn) + `"> <input type="radio" checked  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/` + (v.Images) + `" /><label style="margin-left:10px">` + v.TenDapAn + `</label> </label>`);
                                    }
                                    if (v.IDSTT == 2) {
                                        $('#pnNoiDung').append(` <label class="form-input active-input" for="da_` + (v.DapAn) + `"> <input type="radio" checked  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/` + (v.Images) + `" /><label style="margin-left:10px">` + v.TenDapAn + `</label></label>`);

                                    }
                                    if (v.IDSTT == 3) {
                                        $('#pnNoiDung').append(` <label class="form-input active-input" for="da_` + (v.DapAn) + `"> <input type="radio" checked  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/` + (v.Images) + `" /><label style="margin-left:10px">` + v.TenDapAn + `</label></label>`);
                                    }
                                    if (v.IDSTT == 4) {
                                        $('#pnNoiDung').append(` <label class="form-input active-input" for="da_` + (v.DapAn) + `"> <input type="radio" checked  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/` + (v.Images) + `" /><label style="margin-left:10px">` + v.TenDapAn + `</label></label>`);
                                    }
                                    if (v.IDSTT == 5) {
                                        $('#pnNoiDung').append(` <label class="form-input active-input" for="da_` + (v.DapAn) + `"> <input type="radio" checked  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/` + (v.Images) + `" /><label style="margin-left:10px">` + v.TenDapAn + `</label></label>`);
                                    }
                                    CauHoiKeTiepDem = v.CauHoiKeTiep;
                                    DapAnDem = v.DapAn;
                                    if (STT == 1) {
                                        ID_STT = STT;
                                    }
                                    IDCauHoi = CauHoi;

                                } else {
                                    if (v.IDSTT == 1) {
                                        $('#pnNoiDung').append(` <label class="form-input" for="da_` + (v.DapAn) + `"> <input type="radio"  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/` + (v.Images) + `" /><label style="margin-left:10px">` + v.TenDapAn + `</label></label>`);

                                    }
                                    if (v.IDSTT == 2) {
                                        $('#pnNoiDung').append(` <label class="form-input" for="da_` + (v.DapAn) + `"> <input type="radio"  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/` + (v.Images) + `" /><label style="margin-left:10px">` + v.TenDapAn + `</label></label>`);
                                    }
                                    if (v.IDSTT == 3) {
                                        $('#pnNoiDung').append(` <label class="form-input" for="da_` + (v.DapAn) + `"> <input type="radio"  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/` + (v.Images) + `" /><label style="margin-left:10px">` + v.TenDapAn + `</label></label>`);
                                    }
                                    if (v.IDSTT == 4) {
                                        $('#pnNoiDung').append(` <label class="form-input" for="da_` + (v.DapAn) + `"> <input type="radio"  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/` + (v.Images) + `" /><label style="margin-left:10px">` + v.TenDapAn + `</label></label>`);

                                    }
                                    if (v.IDSTT == 5) {
                                        $('#pnNoiDung').append(` <label class="form-input" for="da_` + (v.DapAn) + `"> <input type="radio"  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/` + (v.Images) + `" /><label style="margin-left:10px">` + v.TenDapAn + `</label></label>`);
                                    }

                                }

                            }
                            else {
                                if (v.IDSTT == 1) {
                                    $('#pnNoiDung').append(` <label class="form-input" for="da_` + (v.DapAn) + `"> <input type="radio"  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/` + (v.Images) + `" /><label style="margin-left:10px">` + v.TenDapAn + `</label></label>`);
                                }
                                if (v.IDSTT == 2) {
                                    $('#pnNoiDung').append(` <label class="form-input" for="da_` + (v.DapAn) + `"> <input type="radio"  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/` + (v.Images) + `" /><label style="margin-left:10px">` + v.TenDapAn + `</label></label>`);

                                }
                                if (v.IDSTT == 3) {
                                    $('#pnNoiDung').append(` <label class="form-input" for="da_` + (v.DapAn) + `"> <input type="radio"  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/` + (v.Images) + `" /><label style="margin-left:10px">` + v.TenDapAn + `</label></label>`);
                                }
                                if (v.IDSTT == 4) {
                                    $('#pnNoiDung').append(` <label class="form-input" for="da_` + (v.DapAn) + `"> <input type="radio"  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/` + (v.Images) + `" /><label style="margin-left:10px">` + v.TenDapAn + `</label></label>`);
                                }
                                if (v.IDSTT == 5) {

                                    $('#pnNoiDung').append(` <label class="form-input" for="da_` + (v.DapAn) + `"> <input type="radio"  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/` + (v.Images) + `" /><label style="margin-left:10px">` + v.TenDapAn + `</label></label>`);

                                }
                                DapAnDem = 0;
                            }


                            //event khi click

                            $('#dahailong_' + v.DapAn).change(function () {

                                CapNhatCauChonMot(CauHoi, v.DapAn, ID, 1, v.CauHoiKeTiep, STT);
                            })

                            $('.form-input input').on("change", function () {
                                $(".form-input").removeClass("active-input");
                                $(this).parent().addClass("active-input");

                            });


                        })
                    }
                    else {
                    }
                });
        }

        function CapNhatCauChonMot(CauHoi, DapAn, ID, Loai, CauHoiKeTiep,STT) {
            CauHoiKeTiepDem = CauHoiKeTiep;
            DapAnDem = DapAn;
            IDCauHoi = CauHoi;
            if (STT == 1) {
                ID_STT = STT;
            }

            $.post('/Home/CapNhatKetQuaCauChonMot', { __RequestVerificationToken: $('input[name="__RequestVerificationToken"]').val(), CauHoi: CauHoi, DapAn: DapAn, ID: ID }).done(function (rs) {
                if (rs.StatusCode > 0) {
                }
                else {
                }
            })
        }

        function CapNhatCauChonNhieu(CauHoi, DapAn, Loai, ID, TrangThai, CauHoiKeTiep,STT) {
            CauHoiKeTiepDem = CauHoiKeTiep;
            DapAnDem = DapAn;
            IDCauHoi = CauHoi;
            if (STT == 1) {
                ID_STT = STT;
            }
            $.post('/Home/CapNhatKetQuaCauChonNhieu', { __RequestVerificationToken: $('input[name="__RequestVerificationToken"]').val(), CauHoi: CauHoi, DapAn: DapAn, ID: ID, Loai: Loai }).done(function (rs) {
                if (rs.StatusCode > 0) {
                }
                else {
                }
            })
        }

        function CapNhatCauText(CauHoi, GhiChu, ID, Loai) {
            CauHoiKeTiepDem = -1;
            DapAnDem = -1;
            IDCauHoi = CauHoi;
            $.post('/Home/CapNhatKetQuaCauText', { __RequestVerificationToken: $('input[name="__RequestVerificationToken"]').val(), CauHoi: CauHoi, GhiChu: GhiChu, ID: ID }).done(function (rs) {
                if (rs.StatusCode > 0) {
                }
                else {
                }
            })
        }

        function Next() {
            if (DapAnDem == 0) {
               DevExpress.ui.notify("Bạn chưa chọn đáp án nên không thể next câu kế tiếp", "warning", 5000);
            }
            else {

                if (CauHoiKeTiepDem == -1) {
                    mang++;
                    if (datasource.length - 1 == mang) {
                        LoadThongTinCauHoi(datasource[mang].CauHoi);
                    }
                    else {
                        LoadThongTinCauHoi(datasource[mang].CauHoi);
                    }

                    dataDaNhap.push(IDCauHoi);
                }
                else {
                    console.log(mang);
                    console.log(CauHoiKeTiepDem-1);
                    if (Number(CauHoiKeTiepDem) == 0) {
                        mang++;
                        if (datasource.length - 1 == mang) {
                            LoadThongTinCauHoi(datasource[mang].CauHoi);
                        }
                        else {

                            LoadThongTinCauHoi(datasource[mang].CauHoi);
                        }
                        dataDaNhap.push(IDCauHoi);
                    }
                    else {
                        if (mang == CauHoiKeTiepDem - 1) {
                            dataDaNhap.push(IDCauHoi);
                            KetThuc();
                        }
                        else {
                            mang = CauHoiKeTiepDem - ID_STT;
                            if (datasource.length - 1 == mang) {
                                LoadThongTinCauHoi(datasource[mang].CauHoi);
                            }
                            else {

                                LoadThongTinCauHoi(datasource[mang].CauHoi);
                            }
                            dataDaNhap.push(IDCauHoi);
                        }
                    }


                }

            }
        }

        function Back() {
            var h = dataDaNhap[dataDaNhap.length - 1];
            $.each(datasource, function (i, v) {
                if (v.CauHoi == h) {
                    mang = i;
                    dataDaNhap.splice(dataDaNhap.length - 1, 1);
                }
            });
            if (mang == 0) {
                LoadThongTinCauHoi(datasource[mang].CauHoi);
            }
            else {
                LoadThongTinCauHoi(datasource[mang].CauHoi);
            }

        }

    function LoadThongTinCauHoiHoanThanh(KeHoach) {

            mau = $('#iMau').html();
            $('#iKhaoSatHoanThanh').empty();
            $.post('/Home/GetKhaoSatCauHoiHoanThanh', { KeHoach: KeHoach })
                .done(function (data) {
                    if (data.length > 0) {
                        $.each(data, function (i, v) {
                            var tieude = '', daluu = '', batbuoc = '';

                            tieude += v.STT + '.' + v.TenCauHoi;
                            daluu = '<span id="1luu_' + v.CauHoi + '" ></span>';
                            batbuoc = v.TenLoaiCauHoi;
                            var dapan = '';

                            if (v.KieuCauHoi == 1) {
                                $('#iKhaoSatHoanThanh').append(mau.replace('repTieuDe', tieude).replace('repDapAn', '<div id="NoiDungCauHoiLoai1_' + v.CauHoi + '"></div>').replace('repDaLuu', daluu).replace('repBatBuoc', batbuoc));
                                DapAnCauHoiChonMotHoanThanh(v.CauHoi, v.DapAnChon, KeHoach, v.GhiChu);
                            }
                            if (v.KieuCauHoi == 2) {
                                dapan = '<textarea type="text"  id=' + v.CauHoi + '_GhiChuLoai2' + ' class="form-control height-auto" placeholder = "Nhập ghi chú" style="height: 100px" >' + v.GhiChu +'</textarea>';
                                $('#iKhaoSatHoanThanh').append(mau.replace('repTieuDe', tieude).replace('repDapAn', dapan).replace('repDaLuu', daluu).replace('repBatBuoc', batbuoc));
                            }

                            if (v.KieuCauHoi == 4) {
                                $('#iKhaoSatHoanThanh').append(mau.replace('repTieuDe', tieude).replace('repDapAn', '<div id="NoiDungCauHoiLoai4_' + v.CauHoi + '"></div>').replace('repDaLuu', daluu).replace('repBatBuoc', batbuoc));
                                DapAnCauHoiChonNhieuHoanThanh(v.CauHoi, v.DapAnChon, KeHoach, v.GhiChu);
                            }
                            if (v.KieuCauHoi == 5) {
                                dapan = '<input type="number"  id=' + v.CauHoi + '_GhiChuLoai5' + ' class="form-control" placeholder = "Nhập số" value="' + v.GhiChu + '">';

                                $('#iKhaoSatHoanThanh').append(mau.replace('repTieuDe', tieude).replace('repDapAn', dapan).replace('repDaLuu', daluu).replace('repBatBuoc', batbuoc));
                            }
                            if (v.KieuCauHoi == 6) {
                                $('#iKhaoSatHoanThanh').append(mau.replace('repTieuDe', tieude).replace('repDapAn', '<div id="NoiDungCauHoiLoai6_' + v.CauHoi + '"></div>').replace('repDaLuu', daluu).replace('repBatBuoc', batbuoc));
                                DapAnCauHoiChonHaiLongHoanThanh(v.CauHoi, v.DapAnChon, KeHoach, v.GhiChu);
                            }
                        })
                    }
                    else {
                    }
                });
        }

        function DapAnCauHoiChonMotHoanThanh(CauHoi, DapAn, ID, GhiChu, STT) {
            $.post('/Home/GetDapAn', { CauHoi: CauHoi, KeHoach: KeHoach })
                .done(function (data) {
                    data = JSON.parse(data.ResponseData);
                    if (data.length > 0) {
                        $.each(data, function (i, v) {
                            if (DapAn != null) {
                                if (DapAn == v.DapAn) {
                                    $('#NoiDungCauHoiLoai1_' + v.CauHoi).append(` <label class="form-input active-input" for="da_` + (v.DapAn) + `"> <input type="radio" checked  dindex="` + i + `" name="qLoai1_` + CauHoi + `" id="da_` + (v.DapAn) + `"> ` + v.TenDapAn + ` </label>`);
                                } else {
                                    $('#NoiDungCauHoiLoai1_' + v.CauHoi).append(` <label class="form-input" for="da_` + (v.DapAn) + `"> <input type="radio" dindex="` + i + `" name="qLoai1_` + CauHoi + `" id="da_` + (v.DapAn) + `"> ` + v.TenDapAn + ` </label>`);
                                }
                            }
                            else {
                                $('#NoiDungCauHoiLoai1_' + v.CauHoi).append(` <label class="form-input" for="da_` + (v.DapAn) + `"> <input type="radio" dindex="` + i + `" name="qLoai1_` + CauHoi + `" id="da_` + (v.DapAn) + `"> ` + v.TenDapAn + ` </label>`);
                            }

                            $('.form-input input').on("change", function () {
                                $(".form-input").removeClass("active-input");
                                $(this).parent().addClass("active-input");

                            });
                        })
                    }
                    else {
                    }
                });
        }


        function DapAnCauHoiChonNhieuHoanThanh(CauHoi, DapAn, ID, GhiChu, STT) {
            $.post('/Home/GetDapAn', { CauHoi: CauHoi, KeHoach: KeHoach })
                .done(function (data) {
                    data = JSON.parse(data.ResponseData);
                    if (data.length > 0) {
                        $.each(data, function (i, v) {
                            $('#NoiDungCauHoiLoai4_' + v.CauHoi).append(` <label class="form-input" for="da_` + (v.DapAn) + `"> <input type="checkbox" dindex="` + i + `" name="qLoai4_` + CauHoi + `" id="da_` + (v.DapAn) + `"> ` + v.TenDapAn + ` </label>`);
                            // gán lại kết quả sau khi lưu
                            if (DapAn != null) {
                                if (DapAn.includes(v.DapAn)) {
                                    $('#da_' + v.DapAn).attr("checked", true);
                                    $("label[for='da_" + v.DapAn + "']").addClass("active-input");
                                }
                                else {
                                    $('#da_' + v.DapAn).attr("checked", false);
                                }
                            }

                        })
                    }
                    else {
                    }
                });
        }

        function DapAnCauHoiChonHaiLongHoanThanh(CauHoi, DapAn, ID, GhiChu, STT) {
            $.post('/Home/GetDapAn', { CauHoi: CauHoi, KeHoach: KeHoach })
                .done(function (data) {
                    data = JSON.parse(data.ResponseData);
                    if (data.length > 0) {
                        $.each(data, function (i, v) {
                            if (DapAn != null) {
                                if (DapAn == v.DapAn) {
                                    if (v.IDSTT == 1) {
                                        //$('#NoiDungCauHoiLoai6_' + v.CauHoi).append(` <label class="form-input active-input" for="da_` + (v.DapAn) + `"> <input type="radio" checked  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/Content/images/khaosat/ratte.png" /><label style="margin-left:10px">` + v.TenDapAn + `</label></label>`);
                                        $('#NoiDungCauHoiLoai6_' + v.CauHoi).append(` <label class="form-input active-input" for="da_` + (v.DapAn) + `"> <input type="radio" checked  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/` + (v.Images) + `" /><label style="margin-left:10px">` + v.TenDapAn + `</label></label>`);
                                    }
                                    if (v.IDSTT == 2) {
                                        //$('#NoiDungCauHoiLoai6_' + v.CauHoi).append(` <label class="form-input active-input" for="da_` + (v.DapAn) + `"> <input type="radio" checked  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/Content/images/khaosat/te.png" /><label style="margin-left:10px">` + v.TenDapAn + `</label></label>`);
                                        $('#NoiDungCauHoiLoai6_' + v.CauHoi).append(` <label class="form-input active-input" for="da_` + (v.DapAn) + `"> <input type="radio" checked  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/` + (v.Images) + `" /><label style="margin-left:10px">` + v.TenDapAn + `</label></label>`);
                                    }
                                    if (v.IDSTT == 3) {
                                        //$('#NoiDungCauHoiLoai6_' + v.CauHoi).append(` <label class="form-input active-input" for="da_` + (v.DapAn) + `"> <input type="radio" checked  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/Content/images/khaosat/binhthuong.png" /><label style="margin-left:10px">` + v.TenDapAn + `</label></label>`);
                                        $('#NoiDungCauHoiLoai6_' + v.CauHoi).append(` <label class="form-input active-input" for="da_` + (v.DapAn) + `"> <input type="radio" checked  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/` + (v.Images) + `" /><label style="margin-left:10px">` + v.TenDapAn + `</label></label>`);
                                    }
                                    if (v.IDSTT == 4) {
                                        //$('#NoiDungCauHoiLoai6_' + v.CauHoi).append(` <label class="form-input active-input" for="da_` + (v.DapAn) + `"> <input type="radio" checked  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/Content/images/khaosat/hailong.png" /><label style="margin-left:10px">` + v.TenDapAn + `</label></label>`);
                                        $('#NoiDungCauHoiLoai6_' + v.CauHoi).append(` <label class="form-input active-input" for="da_` + (v.DapAn) + `"> <input type="radio" checked  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/` + (v.Images) + `" /><label style="margin-left:10px">` + v.TenDapAn + `</label></label>`);
                                    }
                                    if (v.IDSTT == 5) {
                                        //$('#NoiDungCauHoiLoai6_' + v.CauHoi).append(` <label class="form-input active-input" for="da_` + (v.DapAn) + `"> <input type="radio" checked  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/Content/images/khaosat/rathailong.png" /><label style="margin-left:10px">` + v.TenDapAn + `</label></label>`);
                                        $('#NoiDungCauHoiLoai6_' + v.CauHoi).append(` <label class="form-input active-input" for="da_` + (v.DapAn) + `"> <input type="radio" checked  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/` + (v.Images) + `" /><label style="margin-left:10px">` + v.TenDapAn + `</label></label>`);
                                    }

                                }
                                else {
                                    if (v.IDSTT == 1) {

                                        //$('#NoiDungCauHoiLoai6_' + v.CauHoi).append(` <label class="form-input" for="da_` + (v.DapAn) + `"> <input type="radio"  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/Content/images/khaosat/ratte.png" /><label style="margin-left:10px">` + v.TenDapAn + `</label></label>`);
                                        $('#NoiDungCauHoiLoai6_' + v.CauHoi).append(` <label class="form-input" for="da_` + (v.DapAn) + `"> <input type="radio"  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/` + (v.Images) + `" /><label style="margin-left:10px">` + v.TenDapAn + `</label></label>`);
                                    }
                                    if (v.IDSTT == 2) {

                                        //$('#NoiDungCauHoiLoai6_' + v.CauHoi).append(` <label class="form-input" for="da_` + (v.DapAn) + `"> <input type="radio"  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/Content/images/khaosat/te.png" /><label style="margin-left:10px">` + v.TenDapAn + `</label></label>`);
                                        $('#NoiDungCauHoiLoai6_' + v.CauHoi).append(` <label class="form-input" for="da_` + (v.DapAn) + `"> <input type="radio"  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/` + (v.Images) + `" /><label style="margin-left:10px">` + v.TenDapAn + `</label></label>`);
                                    }
                                    if (v.IDSTT == 3) {
                                        //$('#NoiDungCauHoiLoai6_' + v.CauHoi).append(` <label class="form-input" for="da_` + (v.DapAn) + `"> <input type="radio"  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/Content/images/khaosat/binhthuong.png" /><label style="margin-left:10px">` + v.TenDapAn + `</label></label>`);
                                        $('#NoiDungCauHoiLoai6_' + v.CauHoi).append(` <label class="form-input" for="da_` + (v.DapAn) + `"> <input type="radio"  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/` + (v.Images) + `" /><label style="margin-left:10px">` + v.TenDapAn + `</label></label>`);
                                    }
                                    if (v.IDSTT == 4) {
                                        //$('#NoiDungCauHoiLoai6_' + v.CauHoi).append(` <label class="form-input" for="da_` + (v.DapAn) + `"> <input type="radio"  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/Content/images/khaosat/hailong.png" /><label style="margin-left:10px">` + v.TenDapAn + `</label></label>`);
                                        $('#NoiDungCauHoiLoai6_' + v.CauHoi).append(` <label class="form-input" for="da_` + (v.DapAn) + `"> <input type="radio"  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/` + (v.Images) + `" /><label style="margin-left:10px">` + v.TenDapAn + `</label></label>`);
                                    }
                                    if (v.IDSTT == 5) {
                                        //$('#NoiDungCauHoiLoai6_' + v.CauHoi).append(` <label class="form-input" for="da_` + (v.DapAn) + `"> <input type="radio"  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/Content/images/khaosat/rathailong.png" /><label style="margin-left:10px">` + v.TenDapAn + `</label></label>`);
                                        $('#NoiDungCauHoiLoai6_' + v.CauHoi).append(` <label class="form-input" for="da_` + (v.DapAn) + `"> <input type="radio"  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/` + (v.Images) + `" /><label style="margin-left:10px">` + v.TenDapAn + `</label></label>`);
                                    }

                                }

                            }
                            else {
                                if (v.IDSTT == 1) {

                                    //$('#NoiDungCauHoiLoai6_' + v.CauHoi).append(` <label class="form-input" for="da_` + (v.DapAn) + `"> <input type="radio"  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/Content/images/khaosat/ratte.png" /><label style="margin-left:10px">` + v.TenDapAn + `</label></label>`);
                                    $('#NoiDungCauHoiLoai6_' + v.CauHoi).append(` <label class="form-input" for="da_` + (v.DapAn) + `"> <input type="radio"  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/` + (v.Images) + `" /><label style="margin-left:10px">` + v.TenDapAn + `</label></label>`);
                                }
                                if (v.IDSTT == 2) {

                                    //$('#NoiDungCauHoiLoai6_' + v.CauHoi).append(` <label class="form-input" for="da_` + (v.DapAn) + `"> <input type="radio"  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/Content/images/khaosat/te.png" /><label style="margin-left:10px">` + v.TenDapAn + `</label></label>`);
                                    $('#NoiDungCauHoiLoai6_' + v.CauHoi).append(` <label class="form-input" for="da_` + (v.DapAn) + `"> <input type="radio"  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/` + (v.Images) + `" /><label style="margin-left:10px">` + v.TenDapAn + `</label></label>`);
                                }
                                if (v.IDSTT == 3) {
                                    //$('#NoiDungCauHoiLoai6_' + v.CauHoi).append(` <label class="form-input" for="da_` + (v.DapAn) + `"> <input type="radio"  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/Content/images/khaosat/binhthuong.png" /><label style="margin-left:10px">` + v.TenDapAn + `</label></label>`);
                                    $('#NoiDungCauHoiLoai6_' + v.CauHoi).append(` <label class="form-input" for="da_` + (v.DapAn) + `"> <input type="radio"  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/` + (v.Images) + `" /><label style="margin-left:10px">` + v.TenDapAn + `</label></label>`);
                                }
                                if (v.IDSTT == 4) {
                                    //$('#NoiDungCauHoiLoai6_' + v.CauHoi).append(` <label class="form-input" for="da_` + (v.DapAn) + `"> <input type="radio"  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/Content/images/khaosat/hailong.png" /><label style="margin-left:10px">` + v.TenDapAn + `</label></label>`);
                                    $('#NoiDungCauHoiLoai6_' + v.CauHoi).append(` <label class="form-input" for="da_` + (v.DapAn) + `"> <input type="radio"  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/` + (v.Images) + `" /><label style="margin-left:10px">` + v.TenDapAn + `</label></label>`);
                                }
                                if (v.IDSTT == 5) {
                                    //$('#NoiDungCauHoiLoai6_' + v.CauHoi).append(` <label class="form-input" for="da_` + (v.DapAn) + `"> <input type="radio"  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/Content/images/khaosat/rathailong.png" /><label style="margin-left:10px">` + v.TenDapAn + `</label></label>`);
                                    $('#NoiDungCauHoiLoai6_' + v.CauHoi).append(` <label class="form-input" for="da_` + (v.DapAn) + `"> <input type="radio"  dindex="` + i + `" name="qLoai6_` + CauHoi + `" id="dahailong_` + (v.DapAn) + `"><img src="/` + (v.Images) + `" /><label style="margin-left:10px">` + v.TenDapAn + `</label></label>`);
                                }
                            }
                        })
                    }
                    else {
                    }
                });
        }

        function KetThuc() {
            $.post('/Home/GetDanhSachCauHoi', { KeHoach: KeHoach })
                .done(function (data) {
                    if (data.length > 0) {
                        $('#iTieuDeHoanThanhKH').html(data[0].TieuDe);
                        $('#iNoiDungHoanThanhKH').html(data[0].NoiDung);
                        $('#HinhAnhHoanThanhKeHoach').attr('src', data[0].Hinh);
                    }
                    else {
                    }
                });


            LoadThongTinCauHoiHoanThanh(KeHoach);
            $('#mdKhaoSat').modal('hide');
            $('#mdKhaoSatHoanThanh').modal('show');
        }

        function ThayDoi() {
            $('#mdKhaoSatHoanThanh').modal('hide');
            $('#mdKhaoSat').modal('show');
            LoadKhaoSat(KeHoach);
        }

        function KetThucKhaoSat() {
            loadPanel.show();
            $.post('/Home/CapNhatKetThucKhaoSat', { __RequestVerificationToken: $('input[name="__RequestVerificationToken"]').val(), KeHoach: KeHoach }).done(function (rs) {
                if (rs.StatusCode == 1) {
                    DevExpress.ui.notify("Kết quả đã được ghi lại, cảm ơn bạn đã thực hiện khảo sát!", "success", 1000);
                    $('#mdKhaoSatHoanThanh').modal('hide');
                    loadPanel.hide();

                    setTimeout(function () {
                        KiemTraKeHoach();
                        location.reload();
                    },1000)
                }
                else {
                    loadPanel.hide();
                    DevExpress.ui.notify(rs.StatusText, "warning", 5000);
                }

            })
        }


</script>


    <script src="/Content/assets/plugins/global/plugins.bundle.js?v=jquery-3.7.1-bootstrap-5.3.8-fix1"></script>
    <script src="/Scripts/jquery.validate.min.js?v=1.21.0"></script>
    <script src="/Content/assets/js/scripts.bundle.js?v=bootstrap-5.3.8-fix1"></script>
    <script src="/Scripts/jszip.min.js"></script>
    <script src="/Content/lib/js/dx.all.js"></script>
    <script src="/Scripts/globalize.min.js"></script>
    <script src="/Scripts/localization/dx.messages.vi.js"></script>
    <script src="/Scripts/localization/dx.messages.en.js"></script>
    <script src="/Scripts/localization/dx.messages.ja.js"></script>
    <script src="/Scripts/bsc_function.js?v=20260821.attachment2"></script>
    <script src="/Scripts/bsc_function2.js?v=1.0.4"></script>
    <script src="/Scripts/parsley.min.js"></script>
    <script type="text/javascript">
    (function (window, $) {
        if (!$) {
            return;
        }

        function ensureBootstrapJQueryPlugins() {
            if (!window.bootstrap) {
                return;
            }

            var plugins = {
                collapse: window.bootstrap.Collapse,
                modal: window.bootstrap.Modal,
                tab: window.bootstrap.Tab,
                dropdown: window.bootstrap.Dropdown,
                tooltip: window.bootstrap.Tooltip
            };

            Object.keys(plugins).forEach(function (name) {
                var constructor = plugins[name];
                if (constructor && constructor.jQueryInterface && !$.fn[name]) {
                    $.fn[name] = constructor.jQueryInterface;
                    $.fn[name].Constructor = constructor;
                }
            });
        }

        function includeSelf($context, selector) {
            var $items = $context.find(selector);
            if ($context.is(selector)) {
                $items = $items.add($context);
            }
            return $items;
        }

        function mapLegacyBootstrapAttributes(context) {
            var $context = context ? $(context) : $(document);

            includeSelf($context, '[data-dismiss="modal"]').attr('data-bs-dismiss', 'modal');
            includeSelf($context, '[data-backdrop="static"]').attr('data-bs-backdrop', 'static');
            includeSelf($context, '[data-keyboard="false"]').attr('data-bs-keyboard', 'false');
            includeSelf($context, '[data-toggle="collapse"]').attr('data-bs-toggle', 'collapse');
            includeSelf($context, '[data-toggle="tab"]').attr('data-bs-toggle', 'tab');
            includeSelf($context, '[data-toggle="dropdown"]').attr('data-bs-toggle', 'dropdown');
            includeSelf($context, '[data-toggle="modal"]').attr('data-bs-toggle', 'modal');
            includeSelf($context, '[data-target]').each(function () {
                var $item = $(this);
                if (!$item.attr('data-bs-target')) {
                    $item.attr('data-bs-target', $item.attr('data-target'));
                }
            });

            includeSelf($context, '[data-toggle="kt-tooltip"]').each(function () {
                var $item = $(this);
                if (!$item.attr('data-bs-toggle')) {
                    $item.attr('data-bs-toggle', 'tooltip');
                }
                if (!$item.attr('title') && $item.attr('data-original-title')) {
                    $item.attr('title', $item.attr('data-original-title'));
                }
            });
        }

        function initTooltips(context) {
            if (!window.bootstrap || !window.bootstrap.Tooltip) {
                return;
            }

            var $context = context ? $(context) : $(document);
            includeSelf($context, '[data-bs-toggle="tooltip"]').each(function () {
                if (!window.bootstrap.Tooltip.getInstance(this)) {
                    new window.bootstrap.Tooltip(this, {
                        customClass: 'tooltip-custom-bold',
                        trigger: 'hover focus'
                    });
                }
            });
        }

        function getDrawer() {
            var drawerElement = document.getElementById('kt_help');
            if (!drawerElement || !window.KTDrawer) {
                return null;
            }
            return window.KTDrawer.getInstance(drawerElement) || new window.KTDrawer(drawerElement);
        }

        function bindLegacyFilterButtons() {
            $(document)
                .off('click.bscLayoutFilter', '#bsc_filter_panel_toggle,#kt_help_toggle')
                .on('click.bscLayoutFilter', '#bsc_filter_panel_toggle,#kt_help_toggle', function (event) {
                    var drawer = getDrawer();
                    if (drawer) {
                        event.preventDefault();
                        drawer.show();
                    }
                });

            $(document)
                .off('click.bscLayoutFilterClose', '#kt_help_close,#app_filter_panel_close')
                .on('click.bscLayoutFilterClose', '#kt_help_close,#app_filter_panel_close', function (event) {
                    var drawer = getDrawer();
                    if (drawer) {
                        event.preventDefault();
                        drawer.hide();
                    }
                });
        }

        function cleanupStaleOverlays() {
            var hasOpenLayer = $('.modal.show,.drawer-on,.offcanvas-on').length > 0 || $('body').hasClass('modal-open') || $('body').hasClass('quick-user-open');
            if (!hasOpenLayer) {
                $('.drawer-overlay,.offcanvas-overlay').not('.bsc-quick-user-overlay').remove();
            }
        }

        window.BSCMapLegacyBootstrapAttributes = mapLegacyBootstrapAttributes;
        window.BSCCleanupStaleOverlays = cleanupStaleOverlays;

        ensureBootstrapJQueryPlugins();
        mapLegacyBootstrapAttributes(document);
        bindLegacyFilterButtons();
        window.setTimeout(cleanupStaleOverlays, 300);
        window.setTimeout(cleanupStaleOverlays, 1200);

        $(function () {
            ensureBootstrapJQueryPlugins();
            mapLegacyBootstrapAttributes(document);
            initTooltips(document);
            bindLegacyFilterButtons();
            cleanupStaleOverlays();
        });

        $(document).on('hidden.bs.modal', cleanupStaleOverlays);

        if (window.MutationObserver) {
            var observer = new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    Array.prototype.forEach.call(mutation.addedNodes || [], function (node) {
                        if (node.nodeType !== 1) {
                            return;
                        }
                        mapLegacyBootstrapAttributes(node);
                        initTooltips(node);
                    });
                });
            });

            $(function () {
                observer.observe(document.body, { childList: true, subtree: true });
            });
        }
    })(window, window.jQuery);
</script>

    
<style>
    .bsc-web-notification-button {
        position: relative;
        border: 0;
    }

    .bsc-web-notification-button::after {
        position: absolute;
        width: 34px;
        height: 34px;
        border: 1px solid rgba(255, 255, 255, .65);
        border-radius: 50%;
        content: "";
        opacity: 0;
        pointer-events: none;
    }

    .bsc-web-notification-button .flaticon2-notification {
        color: #ffffff;
        font-size: 1.45rem;
        transform-origin: 50% 0;
    }

    .bsc-web-notification-button:hover .flaticon2-notification,
    .bsc-web-notification-button.has-unread .flaticon2-notification {
        animation: bsc-web-notification-ring 2.4s ease-in-out infinite;
    }

    .bsc-web-notification-button.has-unread::after {
        animation: bsc-web-notification-pulse 1.8s ease-out infinite;
    }

    .bsc-web-notification-badge {
        position: absolute;
        top: -4px;
        right: -6px;
        min-width: 18px;
        height: 18px;
        padding: 0 5px;
        border: 2px solid #0066b3;
        border-radius: 10px;
        background-color: #f1416c;
        color: #ffffff;
        font-size: 10px;
        font-weight: 700;
        line-height: 14px;
        text-align: center;
    }

    @keyframes bsc-web-notification-ring {
        0%, 45%, 100% {
            transform: rotate(0);
        }

        5%, 15%, 25%, 35% {
            transform: rotate(14deg);
        }

        10%, 20%, 30%, 40% {
            transform: rotate(-14deg);
        }
    }

    @keyframes bsc-web-notification-pulse {
        0% {
            opacity: .65;
            transform: scale(.75);
        }

        100% {
            opacity: 0;
            transform: scale(1.35);
        }
    }

    .bsc-web-notification-modal .modal-dialog {
        max-width: 980px;
    }

    .bsc-web-notification-modal .modal-content {
        overflow: hidden;
    }

    .bsc-web-notification-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
        padding: 12px 16px;
        border-bottom: 1px solid #eff2f5;
    }

    .bsc-web-notification-tabs {
        display: inline-flex;
        overflow: hidden;
        border: 1px solid #d8e2ee;
        border-radius: 6px;
        background-color: #ffffff;
    }

    .bsc-web-notification-tab {
        min-width: 96px;
        padding: 9px 14px;
        border: 0;
        background-color: #ffffff;
        color: #5e6278;
        font-size: 13px;
        font-weight: 600;
        line-height: 1.25;
        text-align: center;
        transition: background-color .2s ease, color .2s ease;
    }

    .bsc-web-notification-tab + .bsc-web-notification-tab {
        border-left: 1px solid #d8e2ee;
    }

    .bsc-web-notification-tab:hover {
        background-color: #eef8ff;
        color: #009ef7;
    }

    .bsc-web-notification-tab.active {
        color: #ffffff;
        background-color: #009ef7;
    }

    .bsc-web-notification-tab-count {
        display: inline-block;
        min-width: 18px;
        margin-left: 4px;
        padding: 1px 5px;
        border-radius: 10px;
        background-color: rgba(0, 158, 247, .12);
        color: #009ef7;
        font-size: 11px;
    }

    .bsc-web-notification-tab.active .bsc-web-notification-tab-count {
        background-color: rgba(255, 255, 255, .22);
        color: #ffffff;
    }

    .bsc-web-notification-total {
        color: #7e8299;
        font-size: 13px;
        white-space: nowrap;
    }

    .bsc-web-notification-total strong {
        color: #181c32;
    }

    .bsc-web-notification-layout {
        display: grid;
        grid-template-columns: minmax(320px, 42%) 1fr;
        height: 68vh;
        min-height: 420px;
        max-height: 620px;
        overflow: hidden;
    }

    .bsc-web-notification-list {
        height: 100%;
        min-height: 0;
        overflow-y: scroll;
        overscroll-behavior: contain;
        border-right: 1px solid #eff2f5;
        background-color: #fafafa;
        scrollbar-gutter: stable;
    }

    .bsc-web-notification-item {
        display: block;
        width: 100%;
        padding: 14px 16px;
        border: 0;
        border-bottom: 1px solid #eff2f5;
        background-color: #ffffff;
        color: #3f4254;
        text-align: left;
    }

    .bsc-web-notification-item:hover,
    .bsc-web-notification-item.active {
        background-color: #eef8ff;
    }

    .bsc-web-notification-item.is-unread {
        background-color: #f1f9ff;
    }

    .bsc-web-notification-item-title {
        display: flex;
        gap: 8px;
        align-items: flex-start;
        font-size: 13px;
        font-weight: 600;
    }

    .bsc-web-notification-unread-dot {
        flex: 0 0 auto;
        width: 8px;
        height: 8px;
        margin-top: 5px;
        border-radius: 50%;
        background-color: #009ef7;
    }

    .bsc-web-notification-item-meta {
        margin-top: 7px;
        color: #7e8299;
        font-size: 11px;
    }

    .bsc-web-notification-detail {
        height: 100%;
        min-height: 0;
        overflow-y: auto;
        padding: 20px;
        background-color: #ffffff;
    }

    .bsc-web-notification-detail-title {
        color: #181c32;
        font-size: 18px;
        font-weight: 700;
    }

    .bsc-web-notification-detail-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 6px 14px;
        margin: 10px 0 18px;
        color: #7e8299;
        font-size: 12px;
    }

    .bsc-web-notification-detail-content {
        color: #3f4254;
        font-size: 13px;
        line-height: 1.7;
        white-space: pre-wrap;
        word-break: break-word;
    }

    .bsc-web-notification-empty {
        padding: 35px 20px;
        color: #a1a5b7;
        text-align: center;
    }

    @media (max-width: 767px) {
        .bsc-web-notification-layout {
            display: block;
            height: auto;
            min-height: 0;
            max-height: none;
        }

        .bsc-web-notification-list,
        .bsc-web-notification-detail {
            height: 42vh;
            max-height: 42vh;
        }

        .bsc-web-notification-list {
            border-right: 0;
            border-bottom: 1px solid #eff2f5;
        }
    }
</style>

<div id="mdWebNotifications" class="modal fade bsc-web-notification-modal" tabindex="-1" role="dialog" aria-labelledby="mdWebNotificationsTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mdWebNotificationsTitle">
                    <i class="flaticon2-notification text-primary me-2"></i>Th&#244;ng b&#225;o
                </h5>
                <button type="button" class="btn btn-sm btn-icon btn-light" aria-label="Close" onclick="$('#mdWebNotifications').modal('hide');">
                    <i class="flaticon2-cross"></i>
                </button>
            </div>
            <div class="bsc-web-notification-toolbar">
                <div class="bsc-web-notification-tabs" role="group">
                    <button type="button" class="bsc-web-notification-tab active" data-notification-state="0">Chưa đọc <span id="webNotificationUnreadTotal" class="bsc-web-notification-tab-count">0</span></button>
                    <button type="button" class="bsc-web-notification-tab" data-notification-state="1">Đ&#227; đọc <span id="webNotificationReadTotal" class="bsc-web-notification-tab-count">0</span></button>
                </div>
                <select id="webNotificationType" class="form-select form-select-sm" style="width: auto; min-width: 190px;">
                    <option value="0">Tất cả loại th&#244;ng b&#225;o</option>
                </select>
                <div class="bsc-web-notification-total">
                    Tổng: <strong id="webNotificationTotalValue">0</strong> th&#244;ng b&#225;o
                </div>
                <button type="button" id="webNotificationMarkAllRead" class="btn btn-sm btn-primary ms-auto">
                    <i class="flaticon2-check-mark"></i>Đ&#227; xem tất cả
                </button>
            </div>
            <div class="bsc-web-notification-layout">
                <div id="webNotificationList" class="bsc-web-notification-list">
                    <div class="bsc-web-notification-empty">Đang tải...</div>
                </div>
                <div id="webNotificationDetail" class="bsc-web-notification-detail">
                    <div class="bsc-web-notification-empty">Chọn một th&#244;ng b&#225;o để xem nội dung.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    (function () {
        var notificationState = 0;
        var notificationItems = [];
        var notificationTotal = 0;
        var notificationCountTimer = null;
        var notificationCountStopped = false;
        var notificationUrls = {
            list: '/Notifications/GetDanhSach',
            count: '/Notifications/GetSoChuaDoc',
            read: '/Notifications/DanhDauDaDoc',
            readAll: '/Notifications/DanhDauDaDocTatCa'
        };
        var notificationTexts = {
            allTypes: 'Tất cả loại thông báo',
            empty: 'Không có thông báo.',
            select: 'Chọn một thông báo để xem nội dung.',
            sentAt: 'Ngày gửi',
            receivedAt: 'Ngày nhận',
            type: 'Loại',
            processingStatus: 'Trạng thái xử lý',
            complete: 'Đã xử lý',
            incomplete: 'Chưa xử lý',
            loading: 'Đang tải...',
            error: 'Không thể tải danh sách thông báo.'
        };

        function updateNotificationBadge(count) {
            count = Number(count) || 0;
            var $badge = $('#webNotificationBadge');
            $badge.text(count > 99 ? '99+' : count);
            $badge.toggle(count > 0);
            $('.bsc-web-notification-button').toggleClass('has-unread', count > 0);
        }

        function stopNotificationCountPolling() {
            notificationCountStopped = true;
            if (notificationCountTimer) {
                window.clearInterval(notificationCountTimer);
                notificationCountTimer = null;
            }
        }

        function updateNotificationTotal(total) {
            notificationTotal = Number(total) || 0;
            $('#webNotificationTotalValue').text(notificationTotal);
        }

        function updateNotificationStateTotals(unreadTotal, readTotal) {
            $('#webNotificationUnreadTotal').text(Number(unreadTotal) || 0);
            $('#webNotificationReadTotal').text(Number(readTotal) || 0);
        }

        function formatNotificationDate(value) {
            if (!value) {
                return '';
            }

            var match = /\/Date\((\d+)\)\//.exec(value);
            var date = match ? new Date(Number(match[1])) : new Date(value);
            if (isNaN(date.getTime())) {
                return '';
            }

            var pad = function (number) {
                return number < 10 ? '0' + number : number;
            };
            return pad(date.getDate()) + '/' + pad(date.getMonth() + 1) + '/' + date.getFullYear()
                + ' ' + pad(date.getHours()) + ':' + pad(date.getMinutes());
        }

        function renderNotificationTypes(types) {
            var currentValue = $('#webNotificationType').val() || '0';
            var $select = $('#webNotificationType').empty();
            var allTypeTotal = 0;
            $.each(types || [], function (_, item) {
                allTypeTotal += Number(item.SoLuong) || 0;
            });
            $('<option>')
                .val('0')
                .attr('data-name', notificationTexts.allTypes)
                .attr('data-count', allTypeTotal)
                .text(notificationTexts.allTypes + ' (' + allTypeTotal + ')')
                .appendTo($select);
            $.each(types || [], function (_, item) {
                var typeName = $.trim(item.TenThongBao || '');
                var typeTotal = Number(item.SoLuong) || 0;
                $('<option>')
                    .val(item.LoaiThongBao)
                    .attr('data-name', typeName)
                    .attr('data-count', typeTotal)
                    .text(typeName + ' (' + typeTotal + ')')
                    .appendTo($select);
            });
            $select.val(currentValue);
            if ($select.val() === null) {
                $select.val('0');
            }
        }

        function decreaseNotificationTypeTotal(notificationType) {
            function decreaseOption($option) {
                if (!$option.length) {
                    return;
                }

                var count = Math.max(0, (Number($option.attr('data-count')) || 0) - 1);
                $option.attr('data-count', count).text(($option.attr('data-name') || '') + ' (' + count + ')');
            }

            decreaseOption($('#webNotificationType option[value="0"]'));
            decreaseOption($('#webNotificationType option[value="' + notificationType + '"]'));
        }

        function renderNotificationList() {
            var $list = $('#webNotificationList').empty();
            if (!notificationItems.length) {
                $('<div>').addClass('bsc-web-notification-empty').text(notificationTexts.empty).appendTo($list);
                $('#webNotificationDetail').html($('<div>').addClass('bsc-web-notification-empty').text(notificationTexts.select));
                return;
            }

            $.each(notificationItems, function (_, item) {
                var $button = $('<button type="button">')
                    .addClass('bsc-web-notification-item')
                    .toggleClass('is-unread', !item.DaDoc)
                    .data('notification', item);
                var $title = $('<div>').addClass('bsc-web-notification-item-title');
                if (!item.DaDoc) {
                    $('<span>').addClass('bsc-web-notification-unread-dot').appendTo($title);
                }
                $('<span>').text(item.TieuDe || '').appendTo($title);
                $title.appendTo($button);
                $('<div>')
                    .addClass('bsc-web-notification-item-meta')
                    .text((item.TenThongBao || '') + ' - ' + formatNotificationDate(item.NgayGui))
                    .appendTo($button);
                $button.appendTo($list);
            });
        }

        function renderNotificationDetail(item) {
            var $detail = $('#webNotificationDetail').empty();
            $('<div>').addClass('bsc-web-notification-detail-title').text(item.TieuDe || '').appendTo($detail);
            var $meta = $('<div>').addClass('bsc-web-notification-detail-meta').appendTo($detail);
            $('<span>').text(notificationTexts.type + ': ' + (item.TenThongBao || '')).appendTo($meta);
            $('<span>').text(notificationTexts.sentAt + ': ' + formatNotificationDate(item.NgayGui)).appendTo($meta);
            if (item.NgayNhan) {
                $('<span>').text(notificationTexts.receivedAt + ': ' + formatNotificationDate(item.NgayNhan)).appendTo($meta);
            }
            if (item.IsDaXuLy === 1 || item.IsDaXuLy === 2) {
                $('<span>')
                    .text(notificationTexts.processingStatus + ': ' + (item.IsDaXuLy === 2 ? notificationTexts.complete : notificationTexts.incomplete))
                    .appendTo($meta);
            }
            $('<div>').addClass('bsc-web-notification-detail-content').text(item.NoiDung || '').appendTo($detail);
        }

        function markNotificationAsRead(item, $button) {
            if (item.DaDoc) {
                return;
            }

            $.post(notificationUrls.read, {
                thongBao: item.ThongBao,
                __RequestVerificationToken: $('input[name="__RequestVerificationToken"]').val()
            }).done(function (result) {
                if (result.StatusCode !== 1) {
                    DevExpress.ui.notify(result.StatusText || notificationTexts.error, 'warning', 3000);
                    return;
                }

                item.DaDoc = true;
                item.NgayNhan = new Date().toISOString();
                $button.removeClass('is-unread').find('.bsc-web-notification-unread-dot').remove();
                updateNotificationBadge(result.SoChuaDoc);
                updateNotificationStateTotals(result.SoChuaDoc, result.SoDaDoc);
                renderNotificationDetail(item);
                if (notificationState === 0) {
                    updateNotificationTotal(Math.max(0, notificationTotal - 1));
                    decreaseNotificationTypeTotal(item.LoaiThongBao);
                    $button.fadeOut(150, function () {
                        $button.remove();
                        notificationItems = $.grep(notificationItems, function (notificationItem) {
                            return notificationItem.ThongBao !== item.ThongBao;
                        });
                        if (!notificationItems.length) {
                            $('<div>').addClass('bsc-web-notification-empty').text(notificationTexts.empty).appendTo('#webNotificationList');
                        }
                    });
                }
            });
        }

        function loadNotifications() {
            $('#webNotificationList').html($('<div>').addClass('bsc-web-notification-empty').text(notificationTexts.loading));
            $.getJSON(notificationUrls.list, {
                trangThai: notificationState,
                loaiThongBao: Number($('#webNotificationType').val()) || 0
            }).done(function (result) {
                if (result.StatusCode !== 1) {
                    $('#webNotificationList').html($('<div>').addClass('bsc-web-notification-empty').text(result.StatusText || notificationTexts.error));
                    return;
                }

                notificationItems = result.Items || [];
                updateNotificationTotal(result.TongSo);
                updateNotificationStateTotals(result.SoChuaDoc, result.SoDaDoc);
                renderNotificationTypes(result.LoaiThongBao);
                renderNotificationList();
                updateNotificationBadge(result.SoChuaDoc);
            }).fail(function () {
                $('#webNotificationList').html($('<div>').addClass('bsc-web-notification-empty').text(notificationTexts.error));
            });
        }

        window.LoadWebNotificationCount = function () {
            if (notificationCountStopped) {
                return;
            }

            $.getJSON(notificationUrls.count).done(function (result) {
                if (result.StatusCode === 1) {
                    updateNotificationBadge(result.SoChuaDoc);
                }
                else if (result.RequiresLogin === true) {
                    stopNotificationCountPolling();
                }
            }).fail(function (xhr) {
                if (xhr && (xhr.status === 401 || xhr.status === 403)) {
                    stopNotificationCountPolling();
                }
            });
        };

        window.OpenWebNotifications = function () {
            $('#mdWebNotifications').modal('show');
            loadNotifications();
        };

        $(document).ready(function () {
            LoadWebNotificationCount();
            notificationCountTimer = window.setInterval(LoadWebNotificationCount, 60000);

            $(document)
                .off('click.bscWebNotificationItem', '.bsc-web-notification-item')
                .on('click.bscWebNotificationItem', '.bsc-web-notification-item', function () {
                    var $button = $(this);
                    var item = $button.data('notification');
                    $('.bsc-web-notification-item').removeClass('active');
                    $button.addClass('active');
                    renderNotificationDetail(item);
                    markNotificationAsRead(item, $button);
                });

            $('.bsc-web-notification-tabs [data-notification-state]').off('click.bscWebNotificationState').on('click.bscWebNotificationState', function () {
                notificationState = Number($(this).attr('data-notification-state'));
                $('.bsc-web-notification-tabs [data-notification-state]').removeClass('active');
                $(this).addClass('active');
                loadNotifications();
            });

            $('#webNotificationType').off('change.bscWebNotificationType').on('change.bscWebNotificationType', loadNotifications);

            $('#webNotificationMarkAllRead').off('click.bscWebNotificationReadAll').on('click.bscWebNotificationReadAll', function () {
                $.post(notificationUrls.readAll, {
                    __RequestVerificationToken: $('input[name="__RequestVerificationToken"]').val()
                }).done(function (result) {
                    if (result.StatusCode !== 1) {
                        DevExpress.ui.notify(result.StatusText || notificationTexts.error, 'warning', 3000);
                        return;
                    }
                    updateNotificationBadge(0);
                    updateNotificationStateTotals(0, result.SoDaDoc);
                    loadNotifications();
                });
            });
        });
    })();
</script>

    <script type="text/javascript">
        var MaNhanVien = '02114273';

        function OpenQuickUserProfile() {
            var $panel = $('#kt_quick_user');
            if ($panel.length === 0) {
                return;
            }

            $('.bsc-quick-user-overlay').remove();
            $('body').append('<div class="offcanvas-overlay bsc-quick-user-overlay"></div>');
            $('body').addClass('quick-user-open');
            $panel.addClass('offcanvas-on');
        }

        function CloseQuickUserProfile() {
            $('#kt_quick_user').removeClass('offcanvas-on');
            $('body').removeClass('quick-user-open');
            $('.bsc-quick-user-overlay').remove();
        }

        function TaiAnh() {
            var maNhanVien = window.MaNhanVien || '';
            if (maNhanVien) {
                window.open('/Portal/HoSo/TaiHinhTheNhanVienWeb?ID=' + encodeURIComponent(maNhanVien));
            }
        }

        function DoiMatKhau() {
            CloseQuickUserProfile();
            $('#iFormLabel_UpdateMatKhauLayout').text('Đổi mật khẩu');
            $('#layout_MatKhauOLD').val('');
            $('#layout_MatKhauMoi').val('');
            $('#layout_XacNhanMatKhau').val('');
            $('#mdDoiMatKhauLayout').modal('show');
        }

        function UpdateMatKhauLayOut() {
            var matKhauMoi = $('#layout_MatKhauMoi').val();
            var xacNhanMatKhau = $('#layout_XacNhanMatKhau').val();
            var matKhauOld = $('#layout_MatKhauOLD').val();
            var cultureCode = 'vi';
            var title = cultureCode === 'vi' ? 'Thông Báo!' : (cultureCode === 'en' ? 'Notification !' : '通知');
            var passwordRuleText = cultureCode === 'vi'
                ? 'Mật khẩu phải có ít nhất 8 ký tự, phải có 1 chữ số, 1 chữ viết hoa, 1 chữ viết thường, 1 ký tự đặc biệt'
                : (cultureCode === 'en'
                    ? 'Password must have at least 8 characters, must have 1 number, 1 uppercase letter, 1 lowercase letter, 1 special character'
                    : 'パスワードは 8 文字以上で、数字 1 つ、大文字 1 つ、小文字 1 つ、特殊文字 1 つを含む必要があります');

            if (matKhauMoi === '') {
                Swal.fire(title, cultureCode === 'vi' ? 'Bạn chưa nhập mật khẩu mới, xin vui lòng nhập mật khẩu mới của bạn' : (cultureCode === 'en' ? 'You have not entered a new password, please enter your new password' : '新しいパスワードを入力していません。新しいパスワードを入力してください'), 'warning');
                return;
            }

            if (xacNhanMatKhau === '') {
                Swal.fire(title, cultureCode === 'vi' ? 'Bạn chưa nhập lại mật khẩu, xin vui lòng nhập lại mật khẩu của bạn' : (cultureCode === 'en' ? 'You have not re-entered your password, please re-enter your password' : 'パスワードを再入力していません。パスワードを再入力してください'), 'warning');
                return;
            }

            if (!/[A-Z]/.test(matKhauMoi) || !/[a-z]/.test(matKhauMoi) || !/[0-9]/.test(matKhauMoi) || !/[!@#$%^&*()\-=_+\[\]{}|;:',.<>/?]/.test(matKhauMoi) || matKhauMoi.length < 8) {
                Swal.fire({
                    title: title,
                    text: passwordRuleText,
                    icon: 'warning',
                    confirmButtonText: cultureCode === 'vi' ? 'Xác nhận' : (cultureCode === 'en' ? 'Confirm' : '確認'),
                    confirmButtonColor: '#3085d6',
                    allowEnterKey: false
                });
                return;
            }

            if (matKhauMoi !== xacNhanMatKhau) {
                DevExpress.ui.notify('Mật khẩu không khớp', 'warning', 3000);
                return;
            }

            $.post('/Systems/Users/Update_MatKhau', {
                MATKHAU: matKhauMoi,
                MatKhauOLD: matKhauOld,
                __RequestVerificationToken: $('input[name="__RequestVerificationToken"]').val()
            }, function (rs) {
                if (rs.StatusCode == 1) {
                    DevExpress.ui.notify('Cập nhật thành công', 'success', 3000);
                    window.location.href = '/logout';
                } else {
                    DevExpress.ui.notify(rs.StatusText, 'warning', 5000);
                }
            });
        }

        $(document).ready(function () {
            $(document)
                .off('click.bscQuickUser', '#myInfo,#kt_quick_user_toggle')
                .on('click.bscQuickUser', '#myInfo,#kt_quick_user_toggle', function (event) {
                    event.preventDefault();
                    OpenQuickUserProfile();
                });

            $(document)
                .off('click.bscQuickUserClose', '#kt_quick_user_close,.bsc-quick-user-overlay')
                .on('click.bscQuickUserClose', '#kt_quick_user_close,.bsc-quick-user-overlay', function (event) {
                    event.preventDefault();
                    CloseQuickUserProfile();
                });

            $('#iUpdateMatKhauLayOut').off('click.bscLayoutPassword').on('click.bscLayoutPassword', function () {
                UpdateMatKhauLayOut();
            });

        });
    </script>
    
    <script src="/Scripts/parsley.min.js"></script>

    
    <script>
        var columns = [], isLoadingAll = false;
        var loadPanel = $(".loadpanel").dxLoadPanel({
            shadingColor: "rgba(0,0,0,0.4)",
            visible: false,
            showIndicator: true,
            showPane: true,
            shading: true,
            message: 'Load dữ liệu',
            closeOnOutsideClick: false,
        }).dxLoadPanel("instance");
         var dataTrangThaiDuyet = [];
        if ('vi' == 'jp')
            dataTrangThaiDuyet = [{ "ID": 0, "NAME": "全て" }, { "ID": 1, "NAME": "まだ承認されていません" }, { "ID": 2, "NAME": "承認済み" }, { "ID": 3, "NAME": "ごみ" }]
        else if ('vi' == 'en')
            dataTrangThaiDuyet = [{ "ID": 0, "NAME": "All" }, { "ID": 1, "NAME": "Refuse" }, { "ID": 2, "NAME": "Approved" }, { "ID": 3, "NAME": "Not approved yet" }]
        else
            dataTrangThaiDuyet = [{ "ID": 0, "NAME": "Tất cả" }, { "ID": 1, "NAME": "Chưa duyệt" }, { "ID": 2, "NAME": "Đã đồng ý duyệt" }, { "ID": 3, "NAME": "Đã từ chối duyệt" }]
        let f_TrangThai = 1;
        var date = new Date(),
            daysInMonth = new Date(date.getFullYear(), date.getMonth() + 1, 1, -1).getDate(),
            TuNgay_S = Globalize.format(new Date(date.getFullYear(), date.getMonth()), "yyyy-MM-dd"),
            DenNgay_S = Globalize.format(new Date(date.getFullYear(), date.getMonth(), daysInMonth), "yyyy-MM-dd"),
            TuNgay = Globalize.format(new Date(date.getFullYear(), date.getMonth()), "yyyy-MM-dd"),
            DenNgay = Globalize.format(new Date(date.getFullYear(), date.getMonth()), "yyyy-MM-dd");
        var CapTrenTrucTiep = null, CapTrenGianTiep = null;
        var dataSource = new DevExpress.data.CustomStore({
            load: function (loadOptions) {
                var deferred = $.Deferred(), params = {}, filter = [];
                if (loadOptions.sort) {
                    params.sort = JSON.stringify(loadOptions.sort);
                }
                if (loadOptions.filter) {
                    var fil = JSON.stringify(loadOptions.filter);
                    if (fil.indexOf('],"and",[') > 0) {
                        $.each(loadOptions.filter, function (i, v) {
                            if (i % 2 == 0) {
                                filter.push({ Column: v[0], Compare: v[1], Value: v[2] });
                            }
                        });
                    }
                    else {
                        filter.push({ Column: loadOptions.filter[0], Compare: loadOptions.filter[1], Value: loadOptions.filter[2] });
                    }
                }
                if (loadOptions.group) {
                    params.group = JSON.stringify(loadOptions.group);
                }
                if (loadOptions.select) {
                    params.select = JSON.stringify(loadOptions.select);
                }
                if (loadOptions.searchValue) {
                    params.searchValue = loadOptions.searchValue;
                    params.searchOperation = loadOptions.searchOperation;
                    params.searchExpr = loadOptions.searchExpr;
                }
                params.isLoadingAll = isLoadingAll;
                params.Take = loadOptions.take;
                params.Page = loadOptions.skip / params.Take + 1;
                if (params.Page == null || params.Page == undefined || isNaN(params.Page)) {
                    return;
                }
                if (filter.length > 0) {
                    params.Filter = JSON.stringify(filter);
                }
                params.TuNgay = TuNgay_S;
                params.DenNgay = DenNgay_S;
                params.TrangThai = f_TrangThai;
                $.getJSON("/Portal/TangCa/Get_DuyetTangCaThucTe", params).done(function (result) {
                    isLoadingAll = false;
                    BSCSoft.deferred(deferred, result);
                }).fail(function (jqxhr, textStatus, error) {
                        deferred.reject("Data Loading Error");
                    });
                return deferred.promise();
            }
        });

        $(document).ready(function () {
            $('#iDanhSach').height($(window).height() - 300);
            $('.bsc-filter-panel__body').height($(window).height() - 200);

            $("#id_TuNgay_S").dxDateBox({
                value: TuNgay_S,
                useMaskBehavior: true,
                type: "date",
                width: '100%',
                displayFormat: 'dd/MM/yyyy',
                onValueChanged: function (data) {
                    TuNgay_S = Globalize.format(data.value, "yyyy-MM-dd");
                },
            }).dxDateBox("instance");
            $("#id_DenNgay_S").dxDateBox({
                value: DenNgay_S,
                useMaskBehavior: true,
                type: "date",
                width: '100%',
                displayFormat: 'dd/MM/yyyy',
                onValueChanged: function (data) {
                    DenNgay_S = Globalize.format(data.value, "yyyy-MM-dd");
                },
            }).dxDateBox("instance");
            $('#id_TrangThai_S').dxSelectBox({
                dataSource: dataTrangThaiDuyet,
                value: f_TrangThai,
                valueExpr: 'ID',
                displayExpr: 'NAME',
                onValueChanged: function (e) {
                    f_TrangThai = e.value;
                    LoadDanhSach();
                }
            });
            LoadDanhSach();
            $('#iXuatEX').click(function () {
                ExportXLS();
            });
        });
        function ExportXLS() {
            loadPanel.show();
            $.post('/Portal/TangCa/ExportDuyetTangCaThucTe', { TuNgay: TuNgay_S, DenNgay: DenNgay_S, __RequestVerificationToken: $('input[name="__RequestVerificationToken"]').val() }, function (r) {
                if (r.StatusCode > 0) {
                    downloadExcel(r.StatusText, 'Danh sách duyệt tăng ca thực tế');
                } else {
                    DevExpress.ui.notify('Đã có lỗi xảy ra, xin vui lòng thử lại.', "warning", 3000);
                }
                loadPanel.hide();
            });
        }

        function LoadDanhSach() {
            $("#iDanhSach").dxDataGrid({
                remoteOperations: { paging: true, filtering: true },
                dataSource: {
                    store: dataSource
                },
                paging: {
                    pageSize: 15
                },
                selection: {
                    mode: "multiple",
                    selectAllMode: "page" // or "allPages"
                },
                filterRow: {
                    visible: true,
                    applyFilter: "auto"
                },
                showRowLines: true,
                rowAlternationEnabled: true,
                showBorders: true,
                hoverStateEnabled: true,
                allowColumnReordering: true,
                allowColumnResizing: true,
                columnAutoWidth: true,
                pager: {
                    showPageSizeSelector: true,
                    allowedPageSizes: [10, 15, 20, 50, 100],
                    showInfo: true
                },
                columnFixing: {
                    enabled: true
                },
                "export": {
                    enabled: true,
                    fileName: 'Phản hồi chấm công tháng',
                },
                columns: [
                    { dataField: 'Ma', caption: 'Mã nhân viên', width: 120, alignment: "center"},
                    { dataField: 'HoTen', caption: 'Tên người đăng ký', width: 150, alignment: "center"},
                    { dataField: 'Nhom', caption: 'Nhóm', width: 150, alignment: "center"},
                    { dataField: 'ToDoi', caption: 'Tổ đội', width: 150, alignment: "center"},
                    { dataField: 'LyDo', caption: 'Lý do tăng ca', width: 150, },
                    { dataField: 'TenCapTrenTrucTiep', caption: 'Cấp trên trực tiếp', width: 150, alignment: "center"},
                    { dataField: 'TenCapTrenGianTiep', caption: 'Cấp trên gián tiếp', width: 150, alignment: "center" },
                    { dataField: 'TuGioPhut', caption: 'Thời gian bắt đầu TT', dataType: "datetime", format: "dd/MM/yyyy HH:mm", width: 150, alignment: "center" },
                    { dataField: 'DenGioPhut', caption: 'Thời gian kết thúc TT', dataType: "datetime", format: "dd/MM/yyyy HH:mm", width: 150, alignment: "center" },
                    { dataField: 'SoPhutOT', caption: 'Tổng số phút tăng ca', width: 180, alignment: "center"},
                    { dataField: 'TuGioPhutKeHoach', caption: 'Thời gian bắt đầu KH', dataType: "datetime", format: "dd/MM/yyyy HH:mm", width: 150, alignment: "center"},
                    { dataField: 'DenGioPhutKeHoach', caption: 'Thời gian kết thúc KH', dataType: "datetime", format: "dd/MM/yyyy HH:mm", width: 150, alignment: "center" },
                    { dataField: 'SoPhutVuot', caption: 'Chênh lệch', width: 120, alignment: "center",},
                    { dataField: 'LyDoLeaderTuChoi', caption: 'Lý do từ chối', width: 150, },
                    {
                        dataField: 'SoPhutVuot', caption: 'Tình trạng lệch', fixed: true, fixedPosition: "right", width: 150, alignment: "center", cellTemplate: function (container, options) {
                            var data = options.data;
                            var html = '';
                            if (data.SoPhutVuot == 0) {
                                html = 'Bằng KH';
                            }
                            if (data.SoPhutVuot > 0) {
                                html = 'Vượt KH';
                            }
                            if (data.SoPhutVuot < 0) {
                                html = 'Ít hơn KH';
                            }
                            container.html(html);
                        },
                    },
                    {
                        dataField: 'isDuyet', caption: 'Trạng thái duyệt', fixed: true, fixedPosition: "right", width: 150, alignment: "center", cellTemplate: function (container, options) {
                            var data = options.data;
                            var html = '';
                            if (data.isDuyet == -1) {
                                html = 'Quá hạn duyệt đơn';
                            }
                            if (data.isDuyet == 0) {
                                html = 'Từ chối';
                            }
                            if (data.isDuyet == 1) {
                                html = 'Đã duyệt';
                            }
                            if (data.isDuyet == 2) {
                                html = 'Chưa duyệt';
                            }
                            if (data.isDuyet == 3) {
                                html = 'Chờ cấp trên gián tiếp';
                            }

                            container.html(html);
                        },
                    },
                ],
                onContentReady: function (e) {
                    
                    loadPanel.hide();
                },
                onCellPrepared: function (e) {
                    if (e.rowType === "data" && (e.column.dataField === "isDuyet")) {
                        var value = e.value;
                        if (value == -1) {
                            e.cellElement.css({ "background-color": "#ffac0c", 'color': '#ffffff' });
                        }
                        if (value == 0) {
                            e.cellElement.css({ "background-color": "#ff4d4d", 'color': '#ffffff' });
                        }
                        if (value == 1) {
                            e.cellElement.css({ "background-color": "#99ffbb", 'color': '#000000' });
                        }
                        if (value == 2) {
                            e.cellElement.css({ "background-color": "#ecd9c6", 'color': '#000000' });
                        }
                        if (value == 3) {
                            e.cellElement.css({ "background-color": "#b3d9ff", 'color': '#fff' });
                        } 
                    }
                    if (e.rowType === "data" && (e.column.dataField === "SoPhutVuot")) {
                        var value = e.value;
                        if (value == 0) {
                            e.cellElement.css({ "background-color": "#ccffcc", 'color': '#000000' });
                        }
                        if (value < 0) {
                            e.cellElement.css({ "background-color": "#ff6666", 'color': '#ffffff' });
                        }
                        if (value > 0) {
                            e.cellElement.css({ "background-color": "#ffd1b3", 'color': '#ffffff' });
                        }

                    }
                    if (e.rowType === "data" && (e.column.dataField === "SoPhutVuot")) {
                        var value = e.value;
                        if (value == 0) {
                            e.cellElement.css({ "background-color": "#ccffcc", 'color': '#000000' });
                        }
                        if (value < 0) {
                            e.cellElement.css({ "background-color": "#ff6666", 'color': '#ffffff' });
                        }
                        if (value > 0) {
                            e.cellElement.css({ "background-color": "#ffff80", 'color': '#000000' });
                        }
                    }
                },
            });
        }
    </script>

        <script type="text/javascript">
            $(document).ready(function () {
                $('#iDuyet').click(function () {
                    var IsCheckDuyet = 0;
                    $.each($("#iDanhSach").dxDataGrid('instance').getSelectedRowsData(), function () {
                        if (this.isDuyet == -1) {
                            IsCheckDuyet++;
                        }
                    })
                    if (IsCheckDuyet == 0) {
                        Duyet(1);
                    }
                    else {
                        DevExpress.ui.notify('Có đơn quá hạn duyệt', "warning", 3000);
                    }
                  
                });
                $('#iTuChoi').click(function () {
                    var IsCheckDuyet = 0;
                    $.each($("#iDanhSach").dxDataGrid('instance').getSelectedRowsData(), function () {
                        if (this.isDuyet == -1) {
                            IsCheckDuyet++;
                        }
                    })
                    if (IsCheckDuyet == 0) {
                        TuChoi(0);
                    }
                    else {
                            DevExpress.ui.notify('Có đơn quá hạn duyệt', "warning", 3000);
                    }
                });
            });
            function Duyet(TrangThai) {
                var tangca_ID = [];
                $.each($("#iDanhSach").dxDataGrid('instance').getSelectedRowsData(), function () {
                    tangca_ID.push(this.OT);
                })
                var DuyetGianTiep_ID = [];
                $.each($("#iDanhSach").dxDataGrid('instance').getSelectedRowsData(), function () {
                    DuyetGianTiep_ID.push(this.DuyetGianTiep);
                })
                var IsDuyet_ID = [];
                $.each($("#iDanhSach").dxDataGrid('instance').getSelectedRowsData(), function () {
                    IsDuyet_ID.push(this.isDuyet);
                })

                if (tangca_ID.join(',').length > 0) {
                    $('#mod_Duyet').modal('show');
                    $('#iDongY_Duyet').click(function () {
                        $.post('/Portal/TangCa/Update_DuyetTangCaThucTe', { TangCa_ID: tangca_ID.join(','), DuyetGianTiep_ID: DuyetGianTiep_ID.join(','), IsDuyet_ID: IsDuyet_ID.join(','), LyDo: "", TrangThai: TrangThai, __RequestVerificationToken: $('input[name="__RequestVerificationToken"]').val() }, function (rs) {
                            if (rs.StatusCode == 1) {
                                DevExpress.ui.notify('Cập nhật thành công', "success", 3000);
                                LoadDanhSach();
                            } else {
                                DevExpress.ui.notify(rs.StatusText, "warning", 5000);
                            }
                            loadPanel.hide();
                            $('#mod_Duyet').modal('hide');
                        });
                    });
                } else {
                    DevExpress.ui.notify('Bạn cần click chọn trong danh sách', "warning", 3000);
                }
            }
            function TuChoi(TrangThai) {
                var tangca_ID = [];
                $.each($("#iDanhSach").dxDataGrid('instance').getSelectedRowsData(), function () {
                    tangca_ID.push(this.OT);
                })
                var DuyetGianTiep_ID = [];
                $.each($("#iDanhSach").dxDataGrid('instance').getSelectedRowsData(), function () {
                    DuyetGianTiep_ID.push(this.DuyetGianTiep);
                })
                var IsDuyet_ID = [];
                $.each($("#iDanhSach").dxDataGrid('instance').getSelectedRowsData(), function () {
                    IsDuyet_ID.push(this.isDuyet);
                })
                if (tangca_ID.join(',').length > 0) {
                    $('#mod_TuChoi').modal('show');
                    var LyDoTuChoi = $('#iLyDoTuChoi').val();
                    $('#iDongY_TuChoi').click(function () {
                        $.post('/Portal/TangCa/Update_DuyetTangCaThucTe', { TangCa_ID: tangca_ID.join(','), DuyetGianTiep_ID: DuyetGianTiep_ID.join(','), IsDuyet_ID: IsDuyet_ID.join(','), LyDo: LyDoTuChoi, TrangThai: TrangThai, __RequestVerificationToken: $('input[name="__RequestVerificationToken"]').val() }, function (rs) {
                            if (rs.StatusCode == 1) {
                                DevExpress.ui.notify('Cập nhật thành công', "success", 3000);
                                LoadDanhSach();
                            } else {
                                DevExpress.ui.notify(rs.StatusText, "warning", 5000);
                            }
                            loadPanel.hide();
                            $('#mod_TuChoi').modal('hide');
                        });
                    });
                } else {
                    DevExpress.ui.notify('Bạn cần click chọn trong danh sách', "warning", 3000);
                }
            }
        </script>


</body>
</html>
