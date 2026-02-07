function showContent(sectionId, element) {
    // Hide all content sections
    const sections = document.querySelectorAll('.content-section');
    sections.forEach(section => section.classList.remove('active'));

    // Remove 'active' class from all nav links' parent li in the sidebar
    const navLinks = document.querySelectorAll('.sidebar .menu-items li');
    navLinks.forEach(link => link.classList.remove('active'));

    // Show the selected content section
    const activeSection = document.getElementById(sectionId);
    if (activeSection) {
        activeSection.classList.add('active');
        // Update the main header title
        const headerTitle = document.getElementById('main-header-title');
        const sectionTitle = activeSection.querySelector('h2.section-title-hidden');
        if(headerTitle && sectionTitle) {
            headerTitle.innerHTML = sectionTitle.innerHTML;
        }
    }

    // Add 'active' class to the clicked nav link's parent li
    if (element) {
        element.classList.add('active');
        sessionStorage.setItem('instructorActiveTab', sectionId);
    }
}

function initializeDashboardView() {
    const activeTabId = sessionStorage.getItem('instructorActiveTab') || 'my-schedule';
    const activeLink = document.querySelector(`a[onclick*="'${activeTabId}'"]`);

    if (activeLink) {
        showContent(activeTabId, activeLink.parentElement);
    } else {
        // Fallback to the first item if the stored one isn't found
        const firstLink = document.querySelector('.sidebar ul.menu-items li:first-child a');
        showContent('my-schedule', firstLink.parentElement);
    }
}

document.addEventListener('DOMContentLoaded', initializeDashboardView);