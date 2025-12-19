// ===============================
// FULL PAGE MATRIX BACKGROUND
// ===============================
const canvas = document.getElementById('matrix');
const ctx = canvas.getContext('2d');

let fontSize = 40;
let columns;
let drops;

const letters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()";

function resizeCanvas() {
  canvas.width = window.innerWidth;
  canvas.height = window.innerHeight;

  // Adjust font size for smaller screens
  fontSize = window.innerWidth < 600 ? 22 : 40;

  columns = Math.floor(canvas.width / fontSize);
  drops = Array(columns).fill(1);
}

resizeCanvas();

function drawMatrix() {
  ctx.fillStyle = "rgba(0, 0, 0, 0.1)";
  ctx.fillRect(0, 0, canvas.width, canvas.height);

  ctx.fillStyle = "#00ff00";
  ctx.font = fontSize + "px monospace";

  for (let i = 0; i < drops.length; i++) {
    const text = letters[Math.floor(Math.random() * letters.length)];
    ctx.fillText(text, i * fontSize, drops[i] * fontSize);

    if (drops[i] * fontSize > canvas.height && Math.random() > 0.975) {
      drops[i] = 0;
    }
    drops[i]++;
  }
}

setInterval(drawMatrix, 50);

// Resize handler
window.addEventListener('resize', resizeCanvas);

// ===============================
// SCROLLING TICKER
// ===============================
const ticker = document.getElementById('ticker');

const messages = [
  "Thanks to: ALIXSEC • NTB CYBER TEAM • XseC404",
  "Hashtags: #SECURITY_ALERT #CYBER #HACKER_STYLE",
  "Stay alert! Cybersecurity is critical.",
  "Responsible disclosure protects users and data."
];

// Repeat messages for smooth infinite scroll
ticker.textContent = messages.join(" • ") + " • " + messages.join(" • ");
