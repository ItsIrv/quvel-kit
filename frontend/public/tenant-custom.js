/**
 * Test JavaScript for dynamic tenant assets
 * This file demonstrates JavaScript injection and adds console logging
 */

console.log('🎉 Tenant custom JavaScript loaded successfully!');
console.log('📊 Current tenant config:', window.__TENANT_CONFIG__);

// Add a custom greeting message
if (window.__TENANT_CONFIG__) {
  console.log(`👋 Welcome to ${window.__TENANT_CONFIG__.tenantName || 'QuVel Kit'}!`);
  console.log(`🌐 App URL: ${window.__TENANT_CONFIG__.appUrl}`);
  console.log(`🎯 Tenant ID: ${window.__TENANT_CONFIG__.tenantId}`);
}

// This script is loaded with lazy loading (position: body-end, priority: low, loading: lazy)
// Perfect example of a script that can run after everything else is done

// Use requestIdleCallback to run when browser is idle
function showCustomAssetsIndicator() {
  console.log('🚀 Page fully loaded with custom tenant assets (lazy loaded)!');
  
  // Add a subtle indicator that custom assets are active
  const indicator = document.createElement('div');
  indicator.style.cssText = `
    position: fixed;
    top: 10px;
    right: 10px;
    background: linear-gradient(45deg, #667eea, #764ba2);
    color: white;
    padding: 8px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    z-index: 9999;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    animation: fadeInOut 4s ease-in-out;
  `;
  indicator.textContent = '✨ Custom Assets Active (Lazy Loaded)';
  
  // Add the CSS animation
  const style = document.createElement('style');
  style.textContent = `
    @keyframes fadeInOut {
      0%, 100% { opacity: 0; transform: translateX(100px); }
      25%, 75% { opacity: 1; transform: translateX(0); }
    }
  `;
  document.head.appendChild(style);
  document.body.appendChild(indicator);
  
  // Remove the indicator after animation
  setTimeout(() => {
    if (indicator.parentNode) {
      indicator.parentNode.removeChild(indicator);
    }
    if (style.parentNode) {
      style.parentNode.removeChild(style);
    }
  }, 4000);
}

// Wait for DOM to be ready, then use idle callback for lazy execution
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    if ('requestIdleCallback' in window) {
      requestIdleCallback(showCustomAssetsIndicator);
    } else {
      setTimeout(showCustomAssetsIndicator, 100);
    }
  });
} else {
  // DOM already ready
  if ('requestIdleCallback' in window) {
    requestIdleCallback(showCustomAssetsIndicator);
  } else {
    setTimeout(showCustomAssetsIndicator, 100);
  }
}

// Add a simple interactive feature
window.tenantCustomUtils = {
  logConfig: function() {
    console.table(window.__TENANT_CONFIG__);
  },
  
  showAssets: function() {
    if (window.__TENANT_CONFIG__?.assets) {
      console.log('🎨 Loaded tenant assets:', window.__TENANT_CONFIG__.assets);
    } else {
      console.log('❌ No tenant assets configured');
    }
  },
  
  toggleCustomStyles: function() {
    const customStyles = document.querySelector('link[href*="tenant-custom.css"], style[data-tenant="custom"]');
    if (customStyles) {
      customStyles.disabled = !customStyles.disabled;
      console.log('🎨 Custom styles', customStyles.disabled ? 'disabled' : 'enabled');
    }
  }
};

console.log('🛠️ Tenant utilities available: window.tenantCustomUtils');
console.log('   - tenantCustomUtils.logConfig() - Show config in table format');
console.log('   - tenantCustomUtils.showAssets() - Show loaded assets');
console.log('   - tenantCustomUtils.toggleCustomStyles() - Toggle custom CSS');