document.addEventListener('DOMContentLoaded', () => {
    const showContactBtn = document.getElementById('show-contact');
    const closeContactBtn = document.getElementById('close-contact');
    const contactSection = document.getElementById('contact-info');
    const showSolutionsBtn = document.getElementById('explore-more');
    const closeSolutionsBtn = document.getElementById('close-solutions');
    const solutionsSection = document.getElementById('solutions-info');

    // QR Code Generation
    new QRCode(document.getElementById("qrcode"), {
        text: "tel:02666060283",
        width: 180,
        height: 180,
        colorDark: "#000000",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });

    // Helper functions
    const showModal = (modal) => {
        modal.classList.remove('hidden');
        modal.style.animation = 'fadeIn 0.5s ease-out';
    };

    const closeModal = (modal) => {
        modal.classList.add('hidden');
    };

    // Contact Modal Events
    showContactBtn.addEventListener('click', () => showModal(contactSection));
    closeContactBtn.addEventListener('click', () => closeModal(contactSection));
    contactSection.addEventListener('click', (e) => {
        if (e.target === contactSection) closeModal(contactSection);
    });

    // Solutions Modal Events
    showSolutionsBtn.addEventListener('click', () => showModal(solutionsSection));
    closeSolutionsBtn.addEventListener('click', () => closeModal(solutionsSection));
    solutionsSection.addEventListener('click', (e) => {
        if (e.target === solutionsSection) closeModal(solutionsSection);
    });
});
