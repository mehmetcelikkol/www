// script.js - Handles logging visits, clicks, and navigation

document.addEventListener('DOMContentLoaded', () => {
    
    // Function to send log to server
    const sendLog = (actionType, elementInfo = null) => {
        fetch('logger.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action_type: actionType,
                element_info: elementInfo
            })
        })
        .then(response => response.json())
        .then(data => console.log('Log recorded:', data))
        .catch(error => console.error('Error logging:', error));
    };

    // 1. Log page visit immediately
    sendLog('visit', window.location.pathname.split('/').pop() || 'index.html');

    // 2. Handle main grid button clicks
    const gridButtons = document.querySelectorAll('.grid-btn');
    const loadingOverlay = document.getElementById('loading-overlay');
    
    if (gridButtons && loadingOverlay) {
        gridButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const action = btn.getAttribute('data-action');
                const btnName = btn.querySelector('h2').innerText;
                
                // Log the click
                sendLog('click', `Button: ${btnName} (${action})`);
                
                // Show loading screen
                loadingOverlay.style.display = 'flex';
                
                // Simulate backend preparation for 2-3 seconds
                const delay = Math.floor(Math.random() * 1000) + 2000; // 2000ms to 3000ms
                
                setTimeout(() => {
                    // Redirect to agreement page with the action type
                    window.location.href = `agreement.html?type=${action}`;
                }, delay);
            });
        });
    }
});

// Reset loading overlay when navigating back/forward (bfcache support)
window.addEventListener('pageshow', (event) => {
    const loadingOverlay = document.getElementById('loading-overlay');
    if (loadingOverlay) {
        loadingOverlay.style.display = 'none';
    }
});
