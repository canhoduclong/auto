<style>
.mobile-admin-identity{display:none}
.mobile-header-time:before{content:' | '}
@media(max-width:991.98px){
    .mobile-compact-header.acc-topbar,.mobile-admin-header{position:relative!important;z-index:1030!important;overflow:visible!important;background:#fff!important}
    .mobile-compact-header.acc-topbar,.mobile-admin-header>.container-fluid{display:grid!important;grid-template-columns:36px max-content 36px minmax(0,1fr);gap:8px!important;align-items:center;padding:12px!important;flex-wrap:nowrap!important}
    .mobile-compact-header.acc-topbar>div{display:contents!important}
    .mobile-compact-header.acc-topbar>div:first-child>div{display:none!important}
    .mobile-compact-header .js-acc-toggle,.mobile-admin-header .js-global-mobile-menu-toggle{grid-column:1;grid-row:1;margin:0!important;padding:8px!important;width:36px;min-height:44px}
    .mobile-compact-header .mobile-role-switcher,.mobile-admin-header .mobile-role-switcher{grid-column:2;grid-row:1}
    .mobile-compact-header .shared-role-desktop{display:none!important}
    .mobile-compact-header.acc-topbar>div:last-child>span,.mobile-admin-identity{display:block!important;grid-column:4;grid-row:1;font-size:13px;line-height:1.4;min-width:0;white-space:normal!important;overflow-wrap:anywhere}
    .mobile-header-name,.mobile-header-time{display:block}
    .mobile-header-time{font-size:11px;color:#64748b}
    .mobile-header-time:before{content:none}
    .mobile-compact-header.acc-topbar>div:last-child>.dropdown:not(.shared-role-desktop){grid-column:3;grid-row:1}
    .mobile-admin-header>.container-fluid>.ms-auto{display:contents!important}
    .mobile-admin-header>.container-fluid>.ms-auto>.dropdown:first-of-type{display:none!important}
    .mobile-admin-header>.container-fluid>.ms-auto>.dropdown:last-of-type{display:block!important;grid-column:3;grid-row:1}
    .mobile-compact-header .mobile-role-switcher summary,.mobile-admin-header .mobile-role-switcher summary{padding:10px 8px;font-size:14px;max-width:125px;overflow:hidden;text-overflow:ellipsis}
    .mobile-compact-header .mobile-role-options,.mobile-admin-header .mobile-role-options{left:-44px;right:auto;width:min(300px,calc(100vw - 24px));z-index:1060}
    .mobile-compact-header .dropdown-menu,.mobile-admin-header .dropdown-menu{max-width:calc(100vw - 24px);z-index:1060}
    .mobile-compact-header.acc-topbar{grid-template-columns:32px minmax(80px,105px) minmax(0,1fr) 40px 32px;gap:6px!important;padding:10px 8px!important}
    .mobile-compact-header .js-acc-toggle{width:32px;padding:6px!important}
    .mobile-compact-header.acc-topbar>div:last-child>.dropdown:not(.shared-role-desktop){grid-column:auto;grid-row:auto}
    .mobile-compact-header.acc-topbar>div:nth-child(2)>span{display:block!important;grid-column:3;grid-row:1;min-width:0;font-size:13px;line-height:1.4;text-align:right}
    .mobile-compact-header.acc-topbar>.acc-header-account{display:block!important;grid-column:4;grid-row:1;position:relative}
    .mobile-compact-header.acc-topbar .acc-header-notifications{display:block!important;grid-column:5!important;grid-row:1!important;position:relative}
    .mobile-compact-header .acc-header-account>.btn,.mobile-compact-header .acc-header-notifications>.btn{min-height:44px;width:100%;padding:6px 3px;font-size:18px}
    .mobile-compact-header .mobile-header-name{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .mobile-compact-header .mobile-header-time{font-size:10px;white-space:normal;overflow-wrap:normal}
    .mobile-compact-header .mobile-role-options{left:-38px}
    .admin-accounting-secondary-header{display:none!important}
}
</style>
