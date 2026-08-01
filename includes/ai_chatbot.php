<style>
.ai-chatbot {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 9999;
    font-family: 'Inter', sans-serif;
}

.ai-chatbot-btn {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary), #ff4d6d);
    border: none;
    box-shadow: 0 4px 20px rgba(255, 0, 60, 0.3);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.ai-chatbot-btn:hover {
    transform: scale(1.1);
}

.ai-chatbot-window {
    position: absolute;
    bottom: 80px;
    right: 0;
    width: 350px;
    height: 500px;
    background-color: white;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transform-origin: bottom right;
    transition: transform 0.3s ease, opacity 0.3s ease;
}

.ai-chat-header {
    background: linear-gradient(135deg, var(--primary), #ff4d6d);
    color: white;
    padding: 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.ai-avatar {
    width: 36px;
    height: 36px;
    background-color: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.ai-chat-body {
    flex: 1;
    padding: 16px;
    overflow-y: auto;
    background-color: #f8fafc;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.ai-msg {
    display: flex;
    max-width: 85%;
}

.ai-msg.ai-user {
    align-self: flex-end;
}

.ai-msg.ai-bot {
    align-self: flex-start;
}

.ai-msg .msg-bubble {
    padding: 10px 14px;
    border-radius: 16px;
    font-size: 14px;
    line-height: 1.4;
    word-break: break-word;
}

.ai-msg.ai-user .msg-bubble {
    background-color: var(--primary);
    color: white;
    border-bottom-right-radius: 4px;
}

.ai-msg.ai-bot .msg-bubble {
    background-color: white;
    color: #1f2937;
    border: 1px solid #e5e7eb;
    border-bottom-left-radius: 4px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.02);
}

.ai-chat-footer {
    padding: 16px;
    background-color: white;
    border-top: 1px solid #e5e7eb;
}

#ai-chat-input {
    flex: 1;
    border: 1px solid #e5e7eb;
    border-radius: 20px;
    padding: 10px 16px;
    outline: none;
    font-size: 14px;
    transition: border-color 0.2s;
}

#ai-chat-input:focus {
    border-color: var(--primary);
}

.ai-send-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: var(--primary);
    color: white;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background-color 0.2s;
}
.ai-send-btn:hover { background-color: #e60036; }

/* Typing indicator */
.typing-indicator {
    display: flex;
    gap: 4px;
    padding: 6px 12px;
    background-color: white;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    border-bottom-left-radius: 4px;
    width: fit-content;
    align-self: flex-start;
}
.typing-dot {
    width: 6px;
    height: 6px;
    background-color: #9ca3af;
    border-radius: 50%;
    animation: typing 1.4s infinite ease-in-out both;
}
.typing-dot:nth-child(1) { animation-delay: -0.32s; }
.typing-dot:nth-child(2) { animation-delay: -0.16s; }
@keyframes typing {
    0%, 80%, 100% { transform: scale(0); }
    40% { transform: scale(1); }
}
</style>

<div id="ai-chatbot" class="ai-chatbot">
    <button id="ai-chatbot-btn" class="ai-chatbot-btn" onclick="toggleAIChat()">
        <i data-lucide="bot" style="width: 28px; height: 28px; color: white;"></i>
    </button>
    
    <div id="ai-chatbot-window" class="ai-chatbot-window" style="display: none; opacity: 0; transform: scale(0.9);">
        <div class="ai-chat-header">
            <div style="display:flex;align-items:center;gap:10px;">
                <div class="ai-avatar"><i data-lucide="bot" style="width:20px;height:20px;"></i></div>
                <div>
                    <h4 style="margin:0;font-size:15px;font-weight:600;">Trợ lý AI</h4>
                    <span style="font-size:12px;opacity:0.8;">Luôn sẵn sàng hỗ trợ</span>
                </div>
            </div>
            <button onclick="toggleAIChat()" style="background:none;border:none;color:white;cursor:pointer;"><i data-lucide="x" style="width:20px;height:20px;"></i></button>
        </div>
        
        <div id="ai-chat-body" class="ai-chat-body">
            <div class="ai-msg ai-bot">
                <div class="msg-bubble">Xin chào 👋! Mình là trợ lý ảo AI. Bạn muốn tìm sân bóng ở khu vực nào hay có yêu cầu gì đặc biệt không?</div>
            </div>
        </div>
        
        <div class="ai-chat-footer">
            <form id="ai-chat-form" style="display:flex;gap:8px;margin:0;" onsubmit="sendAIMessage(event)">
                <input type="text" id="ai-chat-input" placeholder="VD: tìm sân 7 người ở Ninh Kiều..." required autocomplete="off">
                <button type="submit" class="ai-send-btn"><i data-lucide="send" style="width:16px;height:16px;margin-left:2px;"></i></button>
            </form>
        </div>
    </div>
</div>

<script>
let chatOpen = false;
const chatWindow = document.getElementById('ai-chatbot-window');
const chatBody = document.getElementById('ai-chat-body');
const chatInput = document.getElementById('ai-chat-input');

// Global location variables
let aiUserLat = null;
let aiUserLng = null;

// Thử lấy vị trí ngay khi tải trang
if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            aiUserLat = pos.coords.latitude;
            aiUserLng = pos.coords.longitude;
            console.log("AI Chatbot: Đã lấy được vị trí", aiUserLat, aiUserLng);
        },
        (err) => {
            console.log("AI Chatbot: Không thể lấy vị trí -", err.message);
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
}

function toggleAIChat() {
    chatOpen = !chatOpen;
    if (chatOpen) {
        chatWindow.style.display = 'flex';
        // Trigger reflow
        void chatWindow.offsetWidth;
        chatWindow.style.opacity = '1';
        chatWindow.style.transform = 'scale(1)';
        chatInput.focus();
    } else {
        chatWindow.style.opacity = '0';
        chatWindow.style.transform = 'scale(0.9)';
        setTimeout(() => {
            chatWindow.style.display = 'none';
        }, 300);
    }
}

function appendMessage(sender, text, isHtml = false) {
    const msgDiv = document.createElement('div');
    msgDiv.className = `ai-msg ${sender === 'user' ? 'ai-user' : 'ai-bot'}`;
    
    const bubble = document.createElement('div');
    bubble.className = 'msg-bubble';
    if(isHtml) {
        bubble.innerHTML = text;
    } else {
        bubble.textContent = text;
    }
    
    msgDiv.appendChild(bubble);
    chatBody.appendChild(msgDiv);
    chatBody.scrollTop = chatBody.scrollHeight;
}

function showTyping() {
    const typingDiv = document.createElement('div');
    typingDiv.id = 'ai-typing';
    typingDiv.className = 'typing-indicator';
    typingDiv.innerHTML = '<div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div>';
    chatBody.appendChild(typingDiv);
    chatBody.scrollTop = chatBody.scrollHeight;
}

function hideTyping() {
    const typingDiv = document.getElementById('ai-typing');
    if (typingDiv) {
        typingDiv.remove();
    }
}

function getBaseUrl() {
    // Basic heuristics to determine the api path relative to current domain
    const path = window.location.pathname;
    if(path.includes('/Datsanbongda/')) {
        return '/Datsanbongda/';
    }
    return '/';
}

async function sendAIMessage(e) {
    e.preventDefault();
    const message = chatInput.value.trim();
    if (!message) return;
    
    // User message
    appendMessage('user', message);
    chatInput.value = '';
    
    // Show typing
    showTyping();
    
    try {
        const apiUrl = getBaseUrl() + 'api/ai_chat.php';
        const response = await fetch(apiUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ 
            message: message,
            lat: aiUserLat,
            lng: aiUserLng
        })
    });
    
        const text = await response.text();
        hideTyping();
        try {
            const data = JSON.parse(text);
            appendMessage('bot', data.reply, true);
        } catch (e) {
            // Nếu PHP trả về lỗi/cảnh báo làm hỏng JSON, in thẳng text đó ra để coi lý do
            appendMessage('bot', '⚠️ **Lỗi PHP/Không phải JSON:**<br><pre style="white-space:pre-wrap;font-size:12px;background:#eee;padding:5px;">' + text + '</pre>', true);
            console.error('JSON Parse Error. Raw response:', text);
        }
        
        if(window.lucide) lucide.createIcons();
    } catch (error) {
        hideTyping();
        appendMessage('bot', 'Xin lỗi, hệ thống mạng gặp sự cố. Chi tiết: ' + error.message);
        console.error('AI Error:', error);
    }
}
</script>
