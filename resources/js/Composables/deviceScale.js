export function applyDeviceScale(componentName = '', pageProps = {}) {
    if (typeof document === 'undefined') return;
    const isAuth = componentName.startsWith('Auth/');
    const isSalesPortal = componentName.startsWith('Affiliate/') || pageProps.affiliatePortal === true;
    document.body.classList.toggle('auth-device-scale', isAuth);
    document.body.classList.toggle('workspace-ui-page', !isAuth && !isSalesPortal);
    document.body.style.zoom = isAuth || isSalesPortal ? '100%' : '90%';
}
