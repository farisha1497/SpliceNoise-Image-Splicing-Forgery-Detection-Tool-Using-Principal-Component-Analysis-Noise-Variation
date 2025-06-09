class SessionTimeoutManager {
    constructor(timeoutSeconds = 60) {
        this.timeoutSeconds = timeoutSeconds;
        this.warningThreshold = 15; // Show warning when 15 seconds remain
        this.timer = null;
        this.lastActivity = Date.now();
        this.setupEventListeners();
        this.checkTimeout();
    }

    setupEventListeners() {
        // Reset timer on any user activity
        const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'];
        events.forEach(event => {
            document.addEventListener(event, () => this.resetTimer());
        });
    }

    resetTimer() {
        this.lastActivity = Date.now();
        if (this.timer) {
            clearTimeout(this.timer);
        }
        this.timer = setTimeout(() => this.checkTimeout(), 1000); // Check every second
    }

    checkTimeout() {
        const timePassed = (Date.now() - this.lastActivity) / 1000;
        const timeLeft = this.timeoutSeconds - timePassed;

        if (timeLeft <= 0) {
            // Session has expired
            this.handleTimeout();
        } else if (timeLeft <= this.warningThreshold) {
            // Show warning when less than warningThreshold seconds remain
            this.showWarning(Math.ceil(timeLeft));
        } else {
            // Hide warning if it exists
            this.hideWarning();
        }

        // Continue checking
        this.timer = setTimeout(() => this.checkTimeout(), 1000);
    }

    showWarning(secondsLeft) {
        let warningDiv = document.getElementById('session-timeout-warning');
        if (!warningDiv) {
            warningDiv = document.createElement('div');
            warningDiv.id = 'session-timeout-warning';
            document.body.appendChild(warningDiv);
        }

        warningDiv.innerHTML = `
            <div class="timeout-warning">
                <p>Your session will expire in ${secondsLeft} second${secondsLeft !== 1 ? 's' : ''}.</p>
                <button onclick="sessionTimeoutManager.resetTimer()">Keep Session Active</button>
            </div>
        `;

        // Add styles if not already added
        if (!document.getElementById('timeout-styles')) {
            const styles = document.createElement('style');
            styles.id = 'timeout-styles';
            styles.innerHTML = `
                .timeout-warning {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: #fee2e2;
                    border: 1px solid #ef4444;
                    color: #991b1b;
                    padding: 15px 20px;
                    border-radius: 8px;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                    z-index: 9999;
                    animation: slideIn 0.3s ease-out;
                }
                
                .timeout-warning p {
                    margin: 0 0 10px 0;
                    font-weight: 500;
                }
                
                .timeout-warning button {
                    background: #dc2626;
                    color: white;
                    border: none;
                    padding: 8px 16px;
                    border-radius: 4px;
                    cursor: pointer;
                    font-weight: 500;
                    transition: background-color 0.2s;
                }
                
                .timeout-warning button:hover {
                    background: #b91c1c;
                }
                
                @keyframes slideIn {
                    from {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }
            `;
            document.head.appendChild(styles);
        }
    }

    hideWarning() {
        const warningDiv = document.getElementById('session-timeout-warning');
        if (warningDiv) {
            warningDiv.remove();
        }
    }

    handleTimeout() {
        // Clear the timer
        if (this.timer) {
            clearTimeout(this.timer);
        }

        // Make an AJAX call to end the session
        fetch('logout.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ action: 'timeout' })
        }).finally(() => {
            // Redirect to login page
            window.location.href = 'login.php';
        });
    }
} 