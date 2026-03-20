document.addEventListener('DOMContentLoaded', () => {
    const leftPupil = document.querySelector('.left-pupil');
    const rightPupil = document.querySelector('.right-pupil');
    const passwordInput = document.getElementById('password');
    const usernameInput = document.getElementById('username');
    const body = document.body;

    const maxPupilOffset = 6;

    document.addEventListener('mousemove', (e) => {
        if (body.classList.contains('password-focus')) return;
        if (body.classList.contains('error-state')) return;

        const mouseX = e.clientX;
        const mouseY = e.clientY;

        if (leftPupil && rightPupil) {
            movePupil(leftPupil, mouseX, mouseY);
            movePupil(rightPupil, mouseX, mouseY);
        }
    });

    function movePupil(pupil, mouseX, mouseY) {
        pupil.style.transform = 'translate(0,0)';
        const rect = pupil.getBoundingClientRect();
        
        const eyeCenterX = rect.left + rect.width / 2;
        const eyeCenterY = rect.top + rect.height / 2;

        const angle = Math.atan2(mouseY - eyeCenterY, mouseX - eyeCenterX);
        const distance = Math.min(
            maxPupilOffset, 
            Math.hypot(mouseX - eyeCenterX, mouseY - eyeCenterY) / 25
        );

        const x = Math.cos(angle) * distance;
        const y = Math.sin(angle) * distance;

        pupil.style.transform = `translate(${x}px, ${y}px)`;
    }

    if (usernameInput) {
        usernameInput.addEventListener('focus', () => {
            body.classList.remove('password-focus');
            body.classList.remove('error-state'); 
        });
    }

    if (passwordInput) {
        passwordInput.addEventListener('focus', () => {
            body.classList.add('password-focus');
            body.classList.remove('error-state');
            
            // Tatlı utanma: gözler yana baksın
            if (leftPupil && rightPupil) {
                leftPupil.style.transform = `translate(3px, -2px)`;
                rightPupil.style.transform = `translate(3px, -2px)`;
            }
        });

        passwordInput.addEventListener('blur', () => {
            body.classList.remove('password-focus');
            if (leftPupil && rightPupil) {
                leftPupil.style.transform = `translate(0px, 0px)`;
                rightPupil.style.transform = `translate(0px, 0px)`;
            }
        });
    }

    if (window.formState && window.formState.error) {
        setTimeout(() => {
            if (leftPupil && rightPupil) {
                leftPupil.style.transform = `translate(0px, 3px)`;
                rightPupil.style.transform = `translate(0px, 3px)`;
            }
        }, 100);
    }
});
