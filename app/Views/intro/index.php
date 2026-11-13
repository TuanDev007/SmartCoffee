<!DOCTYPE html>
<html lang="en">
<head><base href="/Your-Cafe-3D/Your-Cafe-3D/site/">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SMARTCOFFEE — Immersive 3D Experience</title>
  
  <!-- Link stylesheet directly to prevent FOUC (Flash of Unstyled Content) -->


  <!-- Google Fonts for Playfair Display & Outfit -->
  <link href="./assets/fonts.css" rel="stylesheet">
<script>
  // Keep existing customizations while migrating the previous shop name.
  try {
    const key = 'your_cafe_editor_config';
    const config = JSON.parse(localStorage.getItem(key) || 'null');
    if (config && config.branding) {
      config.branding.siteName = 'SmartCoffee';
      if (config.branding.welcomeText) config.branding.welcomeText = config.branding.welcomeText.replace(/your cafe|brunivo/gi, 'SmartCoffee');
      localStorage.setItem(key, JSON.stringify(config));
    }
  } catch (_) {}
  </script>
  <script type="module" crossorigin src="./assets/index-Dq-uEGHq.js"></script>
  <link rel="stylesheet" crossorigin href="./assets/index-CADJLKTK.css">
<link rel="icon" type="image/svg+xml" href="./assets/smartcoffee-icon.svg"/>
</head>
<body>
  <!-- Three.js Canvas Container -->
  <div id="canvas-container"></div>

  <!-- Scroll spacer for GSAP scroll triggered timeline (1800vh) -->
  <div id="scroll-spacer"></div>

  <!-- Loader Overlay -->
  <div id="loader-overlay">
    <div class="loader-content">
      <h1 class="loader-title">SMARTCOFFEE</h1>
      <p class="loader-subtitle">SPECIALTY COFFEE ROASTERS</p>
      <div class="loader-bar-outer" id="loader-bar-container" style="transition: opacity 0.3s ease;">
        <div id="loader-bar-inner"></div>
      </div>
      <button id="btn-loader-enter" class="cta-button" style="display: none; opacity: 0; transition: opacity 0.5s ease; margin-top: 20px; width: auto; padding: 12px 36px; letter-spacing: 0.1em; font-weight: 700;">ENTER EXPERIENCE</button>
    </div>
  </div>

  <!-- Cinematic Welcome Overlay -->
  <div id="welcome-text-overlay" class="welcome-overlay-text">
    <h2>WELCOME TO SMARTCOFFEE</h2>
    <p>Scroll down to explore our roasting craft & cafe interior</p>
  </div>

  <!-- Floating Header (Logo and Cart Badge) -->
  <header class="floating-header">
    <div class="logo">SMARTCOFFEE</div>
    <div class="header-buttons">
      <button id="btn-feedback-toggle" class="feedback-toggle-btn" aria-label="Open Contact & Feedback">
        <span class="feedback-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
          </svg>
        </span>
      </button>
      <button id="btn-cart-toggle" class="cart-toggle-btn" aria-label="Open Cart">
        <span class="cart-icon">🛒</span>
        <span id="cart-badge-count" class="cart-count-badge">0</span>
      </button>
    </div>
  </header>

  <!-- Toast Notification when products are added -->
  <div id="cart-toast" class="toast-notification">
    <span>☕ Item added to order!</span>
  </div>

  <!-- Scroll Down Hint -->
  <div id="hero-scroll-hint" class="scroll-down-hint">
    SCROLL TO EXPLORE
    <div class="mouse-icon">
      <div class="wheel"></div>
    </div>
  </div>

  <!-- Glass Pill Labels Faded In/Out on Scroll Checks -->
  <div id="label-gallery" class="glass-pill">
    <span class="pill-icon">🖼️</span>
    <span>Our Story & Gallery</span>
  </div>

  <div id="label-menu" class="glass-pill">
    <span class="pill-icon">📋</span>
    <span>Interactive Menu Selection</span>
  </div>

  <div id="label-brewing" class="glass-pill">
    <span class="pill-icon">☕</span>
    <span>Artisan Coffee Grinding & Craft</span>
  </div>

  <div id="label-order" class="glass-pill">
    <span class="pill-icon">🛍️</span>
    <span>Click the cups on the wall to order!</span>
  </div>

  <!-- Cart Drawer Panel (Leather Book Menu) -->
  <div id="cart-drawer-panel" class="cart-drawer">
    <!-- Decorative book split spine -->
    <div class="cart-book-spine"></div>
    
    <!-- Left page: Story and standards -->
    <div class="cart-book-page left-page">
      <h2 class="book-title">SMARTCOFFEE</h2>
      <div class="book-divider"></div>
      <p class="book-story">Every bean we roast represents a journey of precision and craft. From our dual-boiler extraction to hand-poured microfoam, your coffee is brewed to perfection.</p>
      <div class="book-section">
        <h4>Brewing Excellence</h4>
        <ul>
          <li>☕ 9 Bars of pressure extraction</li>
          <li>🌡️ 93°C PID temperature control</li>
          <li>🥛 Organic oat, almond, and whole milks</li>
          <li>🌱 Premium single-origin beans</li>
        </ul>
      </div>
      <div class="book-footer-text">Slow Down · Savor the Craft</div>
    </div>

    <!-- Right page: Items list and checkout -->
    <div class="cart-book-page right-page">
      <div class="cart-drawer-header">
        <h3>YOUR SELECTION</h3>
        <button id="btn-close-cart" class="close-drawer-btn">&times;</button>
      </div>
      
      <div id="cart-items-list" class="cart-items-container">
        <p class="empty-cart-msg">Your order is empty.<br>Click products on the wall to add them!</p>
      </div>

      <div class="cart-drawer-footer">
        <div class="cart-total-row">
          <span>Total:</span>
          <span id="cart-subtotal-price" class="total-price-value">$0.00</span>
        </div>
        <button id="btn-checkout" class="checkout-btn" disabled>Place Order</button>
      </div>
    </div>
  </div>

  <!-- Bespoke Brew Studio Panel (appears at the end of scroll) -->
  <div id="brew-studio-panel" class="brew-studio glass-panel">
    <div class="studio-header">
      <div>
        <span class="studio-badge">BESPOKE BREW</span>
        <h3>Craft Your Blend</h3>
      </div>
      <div class="studio-price" id="studio-product-price">$4.75</div>
    </div>
    
    <div class="studio-divider"></div>
    
    <!-- Item Selector -->
    <div class="studio-group">
      <label>Choose Blend</label>
      <div class="studio-options" id="studio-blend-options">
        <button class="option-btn active" data-id="latte">Latte</button>
        <button class="option-btn" data-id="cappuccino">Cappuccino</button>
        <button class="option-btn" data-id="espresso">Espresso</button>
        <button class="option-btn" data-id="mocha">Mocha</button>
        <button class="option-btn" data-id="coldbrew">Cold Brew</button>
      </div>
    </div>
    
    <!-- Size Selector -->
    <div class="studio-group">
      <label>Size</label>
      <div class="studio-options" id="studio-size-options">
        <button class="option-btn" data-val="Single">Single</button>
        <button class="option-btn active" data-val="Double">Double</button>
        <button class="option-btn" data-val="Triple">Triple</button>
      </div>
    </div>

    <!-- Milk Selector -->
    <div class="studio-group" id="studio-milk-group">
      <label>Milk Option</label>
      <div class="studio-options" id="studio-milk-options">
        <button class="option-btn active" data-val="Whole Milk">Whole</button>
        <button class="option-btn" data-val="Oat Milk">Oat</button>
        <button class="option-btn" data-val="Almond Milk">Almond</button>
      </div>
    </div>

    <!-- Syrup Selector -->
    <div class="studio-group">
      <label>Sweetener / Syrup</label>
      <div class="studio-options" id="studio-sweetener-options">
        <button class="option-btn active" data-val="None">None</button>
        <button class="option-btn" data-val="Vanilla">Vanilla</button>
        <button class="option-btn" data-val="Caramel">Caramel</button>
      </div>
    </div>

    <button id="btn-studio-add" class="cta-button" data-home-url="/brunivo/index.html">Vào trang chủ SmartCoffee →</button>
  </div>

  <!-- Success Message Modal -->
  <div id="success-message-modal" class="success-modal glass-panel">
    <span class="success-icon">🎉</span>
    <h3>ORDER CONFIRMED!</h3>
    <p>Your specialty coffee is being handcrafted. Prepare for a delicious brew!</p>
    <button id="btn-close-success" class="cta-button">Awesome</button>
  </div>

  <!-- Contact & Feedback Modal -->
  <div id="feedback-modal" class="feedback-modal glass-panel">
    <button id="btn-close-feedback" class="close-feedback-btn" aria-label="Close Contact Panel">&times;</button>
    
    <div class="contact-modal-grid">
      <!-- Left Column: Contact Details -->
      <div class="contact-info-panel">
        <h3>Contact Us</h3>
        <p class="contact-subtitle">SMARTCOFFEE Specialty Roasters</p>
        <div class="contact-divider"></div>
        
        <div class="contact-details-list">
          <div class="contact-detail-item">
            <span class="detail-icon">📍</span>
            <div class="detail-text">
              <label>Our Roastery</label>
              <p id="contact-val-address">123 Craft Roast Way, Coffee City</p>
            </div>
          </div>
          
          <div class="contact-detail-item">
            <span class="detail-icon">📞</span>
            <div class="detail-text">
              <label>Call Us</label>
              <p id="contact-val-phone">+1 (555) 123-4567</p>
            </div>
          </div>
          
          <div class="contact-detail-item">
            <span class="detail-icon">✉️</span>
            <div class="detail-text">
              <label>Email Support</label>
              <p id="contact-val-email"><a href="mailto:musabattock@gmail.com">musabattock@gmail.com</a></p>
            </div>
          </div>
          
          <div class="contact-detail-item">
            <span class="detail-icon">⏰</span>
            <div class="detail-text">
              <label>Opening Hours</label>
              <p id="contact-val-hours">Mon - Sun: 7:00 AM - 8:00 PM</p>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Right Column: Feedback Form & Success state -->
      <div class="feedback-form-panel">
        <h3>Send Feedback</h3>
        <p class="feedback-desc">Roasting and brewing to perfection. Tell us how we did!</p>
        
        <form id="feedback-form" class="feedback-form">
          <div class="rating-container">
            <label>Your Rating</label>
            <div class="star-rating" id="feedback-stars">
              <span class="star" data-rating="1">★</span>
              <span class="star" data-rating="2">★</span>
              <span class="star" data-rating="3">★</span>
              <span class="star" data-rating="4">★</span>
              <span class="star" data-rating="5">★</span>
            </div>
          </div>
          <div class="feedback-field">
            <input type="text" id="fb-name" placeholder="Your Name" required />
          </div>
          <div class="feedback-field">
            <input type="email" id="fb-email" placeholder="Your Email" required />
          </div>
          <div class="feedback-field">
            <textarea id="fb-message" placeholder="Your feedback..." rows="3" required></textarea>
          </div>
          <button type="submit" class="cta-button">Submit Feedback</button>
        </form>

        <div id="feedback-success-state" class="feedback-success-state">
          <span class="success-icon">✨</span>
          <h3>THANK YOU!</h3>
          <p>Your feedback has been received. We appreciate your support!</p>
          <p class="feedback-email-ref">Direct contact: <a href="mailto:musabattock@gmail.com">musabattock@gmail.com</a></p>
          <button id="btn-feedback-ok" class="cta-button">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Floating Social Media Links (bottom-left) -->
  <div id="floating-socials" class="floating-socials"></div>

  <!-- Editor Toggle Button (Floating gear icon, bottom-right) -->
  <button id="editor-toggle-btn" class="editor-toggle-btn" aria-label="Open Site Editor" title="Site Editor">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
    </svg>
  </button>

  <!-- Editor Side Panel (content generated by editor.js) -->
  <div id="editor-panel" class="editor-panel"></div>

  <!-- Vite entry script -->

</body>
</html>
