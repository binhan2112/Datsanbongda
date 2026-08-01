document.addEventListener('DOMContentLoaded', function() {
    var chatBox = document.getElementById('chat-box');
    var chatForm = document.getElementById('chat-form');
    var chatInput = document.getElementById('chat-input');
    
    if (!chatBox || !chatForm || !chatInput) return;

    var fieldId = chatBox.getAttribute('data-field-id');
    var receiverId = chatBox.getAttribute('data-receiver-id');
    var isAdminChat = chatBox.getAttribute('data-is-admin-chat') === '1';
    var lastId = parseInt(chatBox.getAttribute('data-last-id')) || 0;

    // Normalize "null" string to actual null
    if (fieldId === 'null' || fieldId === '' || fieldId === '0') {
        fieldId = null;
    }
    if (!receiverId || receiverId === '0') return;
    
    // Detect base path for API calls
    var pathName = window.location.pathname;
    var baseUrl = (pathName.includes('/admin/') || pathName.includes('/owner/') || pathName.includes('/pages/')) ? '../' : './';
    
    function scrollToBottom() {
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    // Remove the "empty" placeholder when first message arrives
    function removeEmptyPlaceholder() {
        var placeholder = chatBox.querySelector('[data-placeholder]');
        if (placeholder) placeholder.remove();
    }

    function appendMessage(msg) {
        removeEmptyPlaceholder();
        
        var div = document.createElement('div');
        div.className = 'chat-message ' + (msg.is_me ? 'me' : 'them');
        if (msg.id && parseInt(msg.id) > lastId) {
            lastId = parseInt(msg.id);
            chatBox.setAttribute('data-last-id', lastId);
        }

        var bubble = document.createElement('div');
        bubble.className = 'message-bubble';
        if (!msg.is_me) {
            bubble.style.backgroundColor = 'rgba(255,255,255,0.05)';
            bubble.style.color = 'var(--text-main, var(--text, inherit))';
            bubble.style.border = '1px solid var(--border-color, var(--border, #e2e8f0))';
        }
        bubble.innerHTML = msg.content;

        var time = document.createElement('div');
        time.className = 'message-time';
        time.innerText = msg.time;

        var timeWrapper = document.createElement('div');
        timeWrapper.style.display = 'flex';
        timeWrapper.style.gap = '8px';
        if (msg.is_me) timeWrapper.style.justifyContent = 'flex-end';

        // Nút dịch (chỉ hiển thị cho tin nhắn của người khác)
        if (!msg.is_me) {
            var translateBtn = document.createElement('button');
            translateBtn.className = 'translate-btn';
            translateBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg> Dịch';
            translateBtn.style.cssText = 'background: none; border: none; font-size: 10px; color: var(--primary, #00bfa6); cursor: pointer; padding: 0 5px; display: inline-flex; align-items: center; gap: 3px;';
            translateBtn.setAttribute('data-text', msg.content.replace(/<br\s*[\/]?>/gi, '\n'));
            timeWrapper.appendChild(translateBtn);
        }
        timeWrapper.appendChild(time);

        div.appendChild(bubble);
        div.appendChild(timeWrapper);
        
        chatBox.appendChild(div);
        scrollToBottom();
    }

    // Xử lý sự kiện click nút dịch (dùng Event Delegation)
    chatBox.addEventListener('click', function(e) {
        var btn = e.target.closest('.translate-btn');
        if (!btn) return;
        
        var textToTranslate = btn.getAttribute('data-text');
        if (!textToTranslate) return;
        
        // Disable button while translating
        btn.disabled = true;
        var originalHtml = btn.innerHTML;
        btn.innerHTML = 'Đang dịch...';
        
        // Gọi API Google Translate miễn phí (auto detect language, dịch sang vi)
        var urlVi = 'https://translate.googleapis.com/translate_a/single?client=gtx&sl=auto&tl=vi&dt=t&q=' + encodeURIComponent(textToTranslate);
        
        fetch(urlVi)
        .then(function(res) { return res.json(); })
        .then(function(data) {
            var translatedText = '';
            data[0].forEach(function(item) {
                if (item[0]) translatedText += item[0];
            });
            var sourceLang = data[2]; // Ngôn ngữ nguồn (vd: 'vi', 'en')
            
            // Nếu ngôn ngữ nguồn là Tiếng Việt, dịch sang Tiếng Anh
            if (sourceLang === 'vi') {
                var urlEn = 'https://translate.googleapis.com/translate_a/single?client=gtx&sl=vi&tl=en&dt=t&q=' + encodeURIComponent(textToTranslate);
                return fetch(urlEn)
                .then(function(res2) { return res2.json(); })
                .then(function(data2) {
                    var textEn = '';
                    data2[0].forEach(function(item) {
                        if (item[0]) textEn += item[0];
                    });
                    return textEn;
                });
            }
            
            return translatedText;
        })
        .then(function(finalText) {
            // Hiển thị phần dịch bên dưới bong bóng chat
            var bubble = btn.closest('.chat-message').querySelector('.message-bubble');
            var transDiv = document.createElement('div');
            transDiv.style.cssText = 'margin-top: 6px; padding-top: 6px; border-top: 1px dashed rgba(128,128,128,0.3); font-size: 13px; font-style: italic; opacity: 0.9;';
            transDiv.innerHTML = finalText.replace(/\n/g, '<br>');
            bubble.appendChild(transDiv);
            
            // Xóa nút dịch để không dịch lại
            btn.remove();
        })
        .catch(function(err) {
            console.error('Translation error:', err);
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            alert('Lỗi dịch vụ dịch thuật.');
        });
    });

    // Handle form submit via AJAX
    chatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        var content = chatInput.value.trim();
        if (!content) return;

        chatInput.value = '';
        chatInput.focus();

        var formData = new FormData();
        formData.append('content', content);
        formData.append('receiver_id', receiverId);
        if (fieldId) formData.append('field_id', fieldId);
        if (isAdminChat) formData.append('is_admin', '1');

        fetch(baseUrl + 'api/chat_api.php?action=send', {
            method: 'POST',
            body: formData
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success && data.message) {
                appendMessage(data.message);
            } else {
                console.error('Send error:', data.error);
            }
        })
        .catch(function(err) { console.error('Network error sending message:', err); });
    });

    // Polling for new messages
    function fetchNewMessages() {
        var url = baseUrl + 'api/chat_api.php?action=fetch&last_id=' + lastId + '&receiver_id=' + receiverId;
        if (fieldId) url += '&field_id=' + fieldId;
        if (isAdminChat) url += '&is_admin=1';

        fetch(url)
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success && data.messages && data.messages.length > 0) {
                data.messages.forEach(function(msg) {
                    appendMessage(msg);
                });
            }
        })
        .catch(function(err) { console.error('Polling error:', err); });
    }

    // Poll every 1 second for faster realtime feel
    setInterval(fetchNewMessages, 1000);

    // Initial scroll
    scrollToBottom();
});
