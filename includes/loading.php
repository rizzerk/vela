<div id="loadingScreen" class="loading-screen">
    <div class="loading-content">
        <img src="https://vela5.dcism.org/vela.png" alt="VELA" class="loading-logo">
        <div class="loading-spinner"></div>
        <div class="loading-text">Loading...</div>
    </div>
</div>

<style>
.loading-screen {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: #ffffff;
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    transition: opacity 0.5s ease;
}

.loading-content {
    text-align: center;
}

.loading-logo {
    width: 80px;
    height: 80px;
    margin-bottom: 2rem;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #e2e8f0;
    border-top: 4px solid #1666ba;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
}

.loading-text {
    color: #64748b;
    font-size: 1rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.loading-screen.fade-out {
    opacity: 0;
    pointer-events: none;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        const loadingScreen = document.getElementById('loadingScreen');
        if (loadingScreen) {
            loadingScreen.classList.add('fade-out');
            setTimeout(() => loadingScreen.remove(), 500);
        }
    }, 1000);
});
</script>