# BloodMate Chatbot Implementation Guide

## Overview
This guide provides step-by-step instructions for adding an AI chatbot to the BloodMate blood donation management system. The chatbot helps donors and customers with common queries about blood donation, eligibility, and the platform.

---

## Table of Contents
1. [Prerequisites](#prerequisites)
2. [Step 1: Add CSS Styles](#step-1-add-css-styles)
3. [Step 2: Add Chatbot HTML](#step-2-add-chatbot-html)
4. [Step 3: Add JavaScript Functionality](#step-3-add-javascript-functionality)
5. [Step 4: Add Mobile Responsive Styles](#step-4-add-mobile-responsive-styles)
6. [Testing the Chatbot](#testing-the-chatbot)
7. [Customization Options](#customization-options)
8. [Troubleshooting](#troubleshooting)

---

## Prerequisites

- BloodMate project files already set up
- Basic understanding of HTML, CSS, and JavaScript
- Text editor (VS Code, Sublime Text, etc.)
- Modern web browser for testing

---

## Step 1: Add CSS Styles

### Location
Add the following CSS to your `index.html` file inside the `<style>` tag, after the existing styles (around line 513).

### CSS Code to Add

```css
/* ── CHATBOT WIDGET ── */
.chatbot-widget {
  position: fixed;
  bottom: 2rem;
  right: 2rem;
  z-index: 1000;
  font-family: var(--font-body);
}
.chatbot-toggle {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: var(--blood);
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 20px rgba(192,21,42,0.5);
  transition: transform 0.3s, box-shadow 0.3s;
}
.chatbot-toggle:hover {
  transform: scale(1.1);
  box-shadow: 0 6px 28px rgba(192,21,42,0.6);
}
.chatbot-toggle i {
  font-size: 1.5rem;
  color: var(--white);
}
.chatbot-window {
  position: absolute;
  bottom: 80px;
  right: 0;
  width: 380px;
  max-height: 500px;
  background: var(--charcoal);
  border: 1px solid rgba(192,21,42,0.3);
  border-radius: 12px;
  box-shadow: 0 10px 40px rgba(0,0,0,0.5);
  display: none;
  flex-direction: column;
  overflow: hidden;
}
.chatbot-window.open {
  display: flex;
}
.chatbot-header {
  background: var(--blood);
  padding: 1rem 1.2rem;
  display: flex;
  align-items: center;
  gap: 0.8rem;
}
.chatbot-avatar {
  width: 40px;
  height: 40px;
  background: var(--white);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}
.chatbot-avatar i {
  color: var(--blood);
  font-size: 1.2rem;
}
.chatbot-info h4 {
  font-size: 0.95rem;
  font-weight: 600;
  color: var(--white);
  margin-bottom: 0.2rem;
}
.chatbot-info span {
  font-size: 0.75rem;
  color: rgba(255,255,255,0.8);
}
.chatbot-close {
  margin-left: auto;
  background: none;
  border: none;
  color: var(--white);
  cursor: pointer;
  font-size: 1.2rem;
  opacity: 0.8;
  transition: opacity 0.2s;
}
.chatbot-close:hover {
  opacity: 1;
}
.chatbot-messages {
  flex: 1;
  overflow-y: auto;
  padding: 1rem;
  max-height: 320px;
  background: var(--ash);
}
.chatbot-message {
  margin-bottom: 1rem;
  display: flex;
  gap: 0.8rem;
}
.chatbot-message.bot {
  flex-direction: row;
}
.chatbot-message.user {
  flex-direction: row-reverse;
}
.message-bubble {
  max-width: 80%;
  padding: 0.8rem 1rem;
  border-radius: 12px;
  font-size: 0.85rem;
  line-height: 1.5;
}
.bot .message-bubble {
  background: var(--charcoal);
  color: var(--white);
  border: 1px solid rgba(255,255,255,0.1);
}
.user .message-bubble {
  background: var(--blood);
  color: var(--white);
}
.message-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.bot .message-avatar {
  background: var(--blood);
}
.user .message-avatar {
  background: var(--text-muted);
}
.message-avatar i {
  font-size: 0.9rem;
  color: var(--white);
}
.chatbot-input-area {
  padding: 1rem;
  background: var(--charcoal);
  border-top: 1px solid rgba(255,255,255,0.1);
  display: flex;
  gap: 0.8rem;
}
.chatbot-input {
  flex: 1;
  background: var(--ash);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 8px;
  padding: 0.7rem 1rem;
  color: var(--white);
  font-size: 0.85rem;
  font-family: var(--font-body);
  outline: none;
  transition: border-color 0.2s;
}
.chatbot-input:focus {
  border-color: var(--blood);
}
.chatbot-input::placeholder {
  color: var(--text-muted);
}
.chatbot-send {
  width: 40px;
  height: 40px;
  background: var(--blood);
  border: none;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s;
}
.chatbot-send:hover {
  background: var(--blood-bright);
}
.chatbot-send i {
  color: var(--white);
  font-size: 0.9rem;
}
.chatbot-quick-actions {
  padding: 0.5rem 1rem;
  background: var(--ash);
  border-top: 1px solid rgba(255,255,255,0.05);
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}
.quick-action-btn {
  background: transparent;
  border: 1px solid rgba(192,21,42,0.3);
  color: var(--text-light);
  padding: 0.4rem 0.8rem;
  border-radius: 20px;
  font-size: 0.75rem;
  cursor: pointer;
  transition: all 0.2s;
}
.quick-action-btn:hover {
  background: var(--blood);
  color: var(--white);
  border-color: var(--blood);
}
.typing-indicator {
  display: flex;
  gap: 4px;
  padding: 0.5rem 0.8rem;
}
.typing-indicator span {
  width: 8px;
  height: 8px;
  background: var(--text-muted);
  border-radius: 50%;
  animation: typing 1.4s infinite;
}
.typing-indicator span:nth-child(2) {
  animation-delay: 0.2s;
}
.typing-indicator span:nth-child(3) {
  animation-delay: 0.4s;
}
@keyframes typing {
  0%, 60%, 100% { transform: translateY(0); }
  30% { transform: translateY(-8px); }
}
```

---

## Step 2: Add Chatbot HTML

### Location
Add the following HTML code to your `index.html` file, right after the closing `</footer>` tag and before the `<script>` tag (around line 1108).

### HTML Code to Add

```html
<!-- CHATBOT WIDGET -->
<div class="chatbot-widget">
  <div class="chatbot-window" id="chatbotWindow">
    <div class="chatbot-header">
      <div class="chatbot-avatar">
        <i class="fas fa-robot"></i>
      </div>
      <div class="chatbot-info">
        <h4>BloodMate Assistant</h4>
        <span>Online • Here to help</span>
      </div>
      <button class="chatbot-close" onclick="toggleChatbot()">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <div class="chatbot-messages" id="chatbotMessages">
      <div class="chatbot-message bot">
        <div class="message-avatar">
          <i class="fas fa-robot"></i>
        </div>
        <div class="message-bubble">
          Hello! 👋 I'm your BloodMate Assistant. I can help you with:
          <br><br>
          • Blood donation eligibility<br>
          • Finding nearby donation centers<br>
          • Blood type information<br>
          • Emergency blood requests<br>
          • General questions about BloodMate
          <br><br>
          How can I assist you today?
        </div>
      </div>
    </div>
    <div class="chatbot-quick-actions">
      <button class="quick-action-btn" onclick="sendQuickMessage('Am I eligible to donate?')">Eligibility</button>
      <button class="quick-action-btn" onclick="sendQuickMessage('Where can I donate?')">Donate Centers</button>
      <button class="quick-action-btn" onclick="sendQuickMessage('Blood type compatibility')">Blood Types</button>
      <button class="quick-action-btn" onclick="sendQuickMessage('Emergency blood request')">Emergency</button>
    </div>
    <div class="chatbot-input-area">
      <input type="text" class="chatbot-input" id="chatbotInput" placeholder="Type your message..." onkeypress="handleKeyPress(event)">
      <button class="chatbot-send" onclick="sendMessage()">
        <i class="fas fa-paper-plane"></i>
      </button>
    </div>
  </div>
  <button class="chatbot-toggle" onclick="toggleChatbot()">
    <i class="fas fa-comment-dots"></i button>
  </button>
</div>
```

---

## Step 3: Add JavaScript Functionality

### Location
Add the following JavaScript code to your `index.html` file inside the `<script>` tag, after the existing JavaScript code (around line 1228).

### JavaScript Code to Add

```javascript
// Chatbot functionality
function toggleChatbot() {
  const window = document.getElementById('chatbotWindow');
  window.classList.toggle('open');
  if (window.classList.contains('open')) {
    document.getElementById('chatbotInput').focus();
  }
}

function handleKeyPress(event) {
  if (event.key === 'Enter') {
    sendMessage();
  }
}

function sendQuickMessage(message) {
  document.getElementById('chatbotInput').value = message;
  sendMessage();
}

function sendMessage() {
  const input = document.getElementById('chatbotInput');
  const message = input.value.trim();
  if (!message) return;

  // Add user message
  addMessage(message, 'user');
  input.value = '';

  // Show typing indicator
  showTypingIndicator();

  // Simulate AI response
  setTimeout(() => {
    removeTypingIndicator();
    const response = generateBotResponse(message);
    addMessage(response, 'bot');
  }, 1000 + Math.random() * 1000);
}

function addMessage(text, sender) {
  const messagesContainer = document.getElementById('chatbotMessages');
  const messageDiv = document.createElement('div');
  messageDiv.className = `chatbot-message ${sender}`;
  
  const avatar = document.createElement('div');
  avatar.className = 'message-avatar';
  avatar.innerHTML = sender === 'bot' ? '<i class="fas fa-robot"></i>' : '<i class="fas fa-user"></i>';
  
  const bubble = document.createElement('div');
  bubble.className = 'message-bubble';
  bubble.innerHTML = text;
  
  messageDiv.appendChild(avatar);
  messageDiv.appendChild(bubble);
  messagesContainer.appendChild(messageDiv);
  messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

function showTypingIndicator() {
  const messagesContainer = document.getElementById('chatbotMessages');
  const typingDiv = document.createElement('div');
  typingDiv.className = 'chatbot-message bot';
  typingDiv.id = 'typingIndicator';
  typingDiv.innerHTML = `
    <div class="message-avatar"><i class="fas fa-robot"></i></div>
    <div class="message-bubble">
      <div class="typing-indicator">
        <span></span><span></span><span></span>
      </div>
    </div>
  `;
  messagesContainer.appendChild(typingDiv);
  messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

function removeTypingIndicator() {
  const typingIndicator = document.getElementById('typingIndicator');
  if (typingIndicator) {
    typingIndicator.remove();
  }
}

function generateBotResponse(message) {
  const lowerMessage = message.toLowerCase();
  
  if (lowerMessage.includes('eligible') || lowerMessage.includes('eligibility')) {
    return 'To be eligible to donate blood, you must:<br><br>• Be between 18-65 years old<br>• Weigh at least 50 kg (110 lbs)<br>• Be in good general health<br>• Not have donated blood in the last 3 months<br><br>For a detailed eligibility check, try our <a href="ai-eligibility.html" style="color:var(--blood-bright)">AI Eligibility Test</a>.';
  }
  
  if (lowerMessage.includes('donate') && (lowerMessage.includes('where') || lowerMessage.includes('center'))) {
    return 'You can donate blood at:<br><br>• Registered blood banks in your area<br>• Partner hospitals<br>• Mobile blood donation camps<br><br>Check our <a href="blood-inventory.html" style="color:var(--blood-bright)">Blood Inventory</a> page to find available centers near you.';
  }
  
  if (lowerMessage.includes('blood type') || lowerMessage.includes('compatibility')) {
    return 'Blood type compatibility:<br><br>• <strong>O-</strong> can donate to everyone (universal donor)<br>• <strong>AB+</strong> can receive from everyone (universal recipient)<br>• <strong>A+</strong> can donate to A+ and AB+<br>• <strong>B+</strong> can donate to B+ and AB+<br><br>View our <a href="blood-inventory.html" style="color:var(--blood-bright)">Blood Types</a> section for more details.';
  }
  
  if (lowerMessage.includes('emergency')) {
    return 'For emergency blood requests:<br><br>🚨 Call our 24/7 helpline: +91 123 456 7890<br>🚨 Visit our <a href="database/emergency.html" style="color:var(--blood-bright)">Emergency Portal</a><br>🚨 Contact nearest hospital directly<br><br>Every second counts in emergencies!';
  }
  
  if (lowerMessage.includes('register') || lowerMessage.includes('sign up')) {
    return 'To register as a donor:<br><br>1. Visit our <a href="donor-registration.html" style="color:var(--blood-bright)">Donor Registration</a> page<br>2. Fill in your details<br>3. Complete health questionnaire<br>4. Get verified and start saving lives!<br><br>Registration takes less than 3 minutes.';
  }
  
  if (lowerMessage.includes('request') || lowerMessage.includes('need blood')) {
    return 'To request blood:<br><br>• Use our <a href="recipient-request.html" style="color:var(--blood-bright)">Blood Request</a> form<br>• Provide patient details and blood type<br>• Our system will match you with donors<br>• For urgent needs, use emergency services<br><br>We prioritize critical cases.';
  }
  
  if (lowerMessage.includes('contact') || lowerMessage.includes('help') || lowerMessage.includes('support')) {
    return 'Contact BloodMate:<br><br>📞 Phone: +91 123 456 7890<br>📧 Email: info@bloodmate.com<br>📍 Address: 123 Healthcare Street, Medical City<br><br>Or visit our <a href="contact.html" style="color:var(--blood-bright)">Contact Page</a> for more options.';
  }
  
  // Default response
  return 'I\'m here to help! You can ask me about:<br><br>• Blood donation eligibility<br>• Where to donate blood<br>• Blood type compatibility<br>• Emergency blood requests<br>• How to register as a donor<br>• How to request blood<br><br>What would you like to know?';
}
```

---

## Step 4: Add Mobile Responsive Styles

### Location
Add the following CSS to the existing mobile media query section (around line 773 in the `@media (max-width: 540px)` section).

### CSS Code to Add

```css
@media (max-width: 540px) {
  .types-grid { grid-template-columns: repeat(2, 1fr); }
  .footer-inner { grid-template-columns: 1fr; }
  .chatbot-window {
    width: 320px;
    right: -1rem;
  }
  .chatbot-widget {
    bottom: 1.5rem;
    right: 1.5rem;
  }
}
```

---

## Testing the Chatbot

### 1. Open the File
- Open `index.html` in your web browser
- You should see a red circular chat button in the bottom-right corner

### 2. Test Basic Functionality
- Click the chat button to open the chat window
- Verify the welcome message appears
- Test the quick action buttons (Eligibility, Donate Centers, etc.)
- Type a custom message and press Enter or click send

### 3. Test Responses
Try these test messages:
- "Am I eligible to donate?"
- "Where can I donate blood?"
- "Blood type compatibility"
- "Emergency blood request"
- "How do I register?"
- "Contact information"

### 4. Test Mobile Responsiveness
- Resize your browser window to mobile size
- Verify the chat window adapts properly
- Test all functionality on mobile view

---

## Customization Options

### Change Chatbot Name
Edit line in HTML:
```html
<h4>BloodMate Assistant</h4>
```
Replace "BloodMate Assistant" with your preferred name.

### Modify Quick Action Buttons
Edit the quick action buttons in HTML:
```html
<button class="quick-action-btn" onclick="sendQuickMessage('Your message here')">Button Label</button>
```

### Add Custom Responses
Add new conditions in the `generateBotResponse()` function:
```javascript
if (lowerMessage.includes('your keyword')) {
  return 'Your custom response here';
}
```

### Change Colors
Modify CSS variables in the chatbot styles:
- `var(--blood)` - Primary red color
- `var(--charcoal)` - Dark background
- `var(--ash)` - Lighter background

### Adjust Position
Change the position in `.chatbot-widget` CSS:
```css
.chatbot-widget {
  bottom: 2rem;  /* Adjust vertical position */
  right: 2rem;   /* Adjust horizontal position */
}
```

---

## Troubleshooting

### Chatbot Button Not Visible
- Ensure CSS is properly added to the `<style>` tag
- Check that Font Awesome icons are loaded
- Verify z-index is not being overridden

### Chat Window Won't Open
- Check that JavaScript is added inside `<script>` tag
- Verify `toggleChatbot()` function is defined
- Check browser console for JavaScript errors

### Messages Not Sending
- Verify `sendMessage()` function exists
- Check that input field has correct ID (`chatbotInput`)
- Ensure event handlers are properly attached

### Styling Issues
- Confirm CSS variables are defined in `:root`
- Check for conflicting CSS rules
- Verify Font Awesome CDN is included

### Mobile Display Issues
- Ensure media queries are properly placed
- Check that responsive styles are not overridden
- Test on actual mobile devices

---

## File Structure Reference

```
BloodMate/
├── index.html
│   ├── <head>
│   │   └── <style>
│   │       └── [Add chatbot CSS here]
│   ├── <body>
│   │   ├── [Existing content]
│   │   ├── </footer>
│   │   └── [Add chatbot HTML here]
│   └── <script>
│       └── [Add chatbot JavaScript here]
```

---

## Summary

The chatbot implementation consists of three main components:

1. **CSS Styles** (~230 lines) - Styling for the chat widget, window, messages, and animations
2. **HTML Structure** (~50 lines) - The chatbot widget markup with header, messages, quick actions, and input
3. **JavaScript Functions** (~120 lines) - Toggle, send message, typing indicator, and response generation logic

Total addition: Approximately 400 lines of code

---

## Support

For issues or questions about this implementation:
- Review the troubleshooting section above
- Check browser console for errors
- Verify all code is placed in the correct locations
- Ensure all dependencies (Font Awesome) are loaded

---

**Document Version:** 1.0  
**Last Updated:** June 29, 2026  
**Compatible with:** BloodMate v1.0
