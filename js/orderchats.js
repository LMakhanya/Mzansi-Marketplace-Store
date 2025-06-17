const chatMessages = document.getElementById('chatMessages');
const chatInput = document.getElementById('chatInput');
const sendBtn = document.getElementById('sendBtn');
const unreadDot = document.getElementById('unreadDot');


let sessionId = null;
let lastMessageCount = 0;
let lastDate = null;
let unreadCount = 0;

// Format date for display
function formatDate(date) {
    return date.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
}

// Format time for display
function formatTime(date) {
    return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
}

// Scroll to bottom function
function scrollToBottom() {
    requestAnimationFrame(() => {
        setTimeout(() => {
            chatMessages.scrollTop = chatMessages.scrollHeight;
            unreadCount = 0;
            unreadDot.style.display = 'none';
        }, 50); // Ensure DOM updates before scrolling
    });
}

// Add message to the chat
function addMessage(sender, message, timestamp, isSent) {
    const date = new Date(timestamp);
    const currentDate = formatDate(date);

    // Add date separator if the date changes
    if (lastDate !== currentDate) {
        const separator = document.createElement('div');
        separator.className = 'date-separator';
        separator.innerHTML = `<span>${currentDate}</span>`;
        separator.dataset.date = currentDate;
        chatMessages.appendChild(separator);
        lastDate = currentDate;
    }

    // Add the message with timestamp
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${isSent ? 'sent' : 'received'}`;
    messageDiv.innerHTML = `
        <span class="message-text">${message}</span>
        <span class="message-time">${formatTime(date)}</span>
    `;
    chatMessages.appendChild(messageDiv);

    // Always scroll to bottom after adding messages
    scrollToBottom();
}

// Load initial session and messages
async function loadSession() {
    const response = await fetch('/api/chats/', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'getSession',
            sellerId,
            customerId,
            orderId
        })
    });

    const data = await response.json();
    sessionId = data.sessionId;
    lastMessageCount = data.messages.length;

    const emptyChat = document.getElementById('emptyChat');

    // Clear previous messages
    chatMessages.innerHTML = '';

    if (data.messages.length === 0) {
        emptyChat.style.display = "block"; // Show empty chat image

    } else {
        emptyChat.style.display = "none"; // Hide if messages exist

        data.messages.forEach(msg => {
            addMessage(msg.sender, msg.message, msg.timestamp, msg.sender === sender);
        });

        // Ensure chat scrolls to the bottom after messages load
        setTimeout(scrollToBottom, 100);
    }

    // Start polling for new messages
    pollMessages();
}

// Send message
async function sendMessage() {
    const message = chatInput.value.trim();
    if (!message || !sessionId) return;

    await fetch('/api/chats/', {
        method: `POST`,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'sendMessage',
            sessionId,
            sellerId,
            customerId,
            orderId,
            sender,
            message
        })
    });

    const timestamp = new Date().toISOString();
    // addMessage(sender, message, timestamp, true);
    chatInput.value = '';
}

// Poll for new messages every 2 seconds
async function pollMessages() {
    const response = await fetch('/api/chats/', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'pollMessages',
            sessionId
        })
    });
    const data = await response.json();

    if (data.messages && data.messages.length > lastMessageCount) {
        const newMessages = data.messages.slice(lastMessageCount);
        newMessages.forEach(msg => {
            addMessage(msg.sender, msg.message, msg.timestamp, msg.sender === sender);
        });
        lastMessageCount = data.messages.length;
    }

    setTimeout(pollMessages, 2000);
}

// Event listeners
sendBtn.addEventListener('click', sendMessage);
chatInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') sendMessage();
});
unreadDot.addEventListener('click', scrollToBottom);

let sellerId = '';
let customerId = '';
let orderId = '';
let sellerName = '';
let businessName = '';
const sender = 'customer'; // sender is constant, so it's fine

function openChat(customerID, sellerID, orderNo, sellerFName, bName) {
    setTimeout(() => {
        sellerId = sellerID;
        customerId = customerID;
        orderId = orderNo;
        sellerName = sellerFName;
        businessName = bName;

        document.getElementById('sellerName').innerHTML = sellerFName;
        document.getElementById('business-name').innerHTML = 'STORE NAME: ' + businessName;
        document.getElementById('chat-overlay').style.display = "flex";

        // Initialize chat session
        loadSession();
    }, 1000); // 2-second delay
}


function closeChat() {
    lastDate = null;
    chatMessages.innerHTML = '';
    lastMessageCount = 0;
    unreadCount = 0;
    unreadDot.style.display = 'none';
    sessionId = null;
    chatInput.value = '';
    // Reset variables
    sellerId = '';
    customerId = '';
    orderId = '';
    sellerName = '';
    businessName = '';
    document.getElementById('chat-overlay').style.display = "none";
    pollMessages();
    loadSession();
}