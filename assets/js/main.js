function toggleLoginForm() {
    const portalSelection = document.getElementById('portal-selection');
    const animatedElements = portalSelection.querySelectorAll('.animate-on-scroll');

    if (portalSelection.style.display === 'none' || portalSelection.style.display === '') {
        portalSelection.style.display = 'block';
        portalSelection.scrollIntoView({ behavior: 'smooth', block: 'center' });
        // Trigger animations manually since it's initially hidden
        animatedElements.forEach(el => {
            el.classList.add('is-visible');
        });
    } else {
        portalSelection.style.display = 'none';
        animatedElements.forEach(el => el.classList.remove('is-visible')); // Reset for next time
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // If a login error occurred on the previous page, the user is sent back here.
    // Automatically show the portals so they can try again.
    if (document.body.dataset.loginError) {
        const portalSelection = document.getElementById('portal-selection');
        if (portalSelection) toggleLoginForm();
    }

    // If a registration error occurred, show the registration form automatically.
    // This logic is now handled by the new standalone register.php page.
    /*
    if (document.body.dataset.registerError || document.body.dataset.registerSuccess) {
        showRegisterForm();
    }
    */

    // General scroll-triggered animation observer
    const animatedElements = document.querySelectorAll('.animate-on-scroll');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target); // Stop observing after animation
            }
        });
    }, { threshold: 0.1 });
    
    animatedElements.forEach(el => {
        observer.observe(el);
    });
});
