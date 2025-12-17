// Simple client-side auth helper for static demo
const Auth = {
  isLoggedIn() { return localStorage.getItem('isLoggedIn') === '1'; },
  getUser() { return {
    email: localStorage.getItem('user_email') || null,
    name: localStorage.getItem('user_name') || null,
    role: localStorage.getItem('user_role') || null
  }; },
  requireLogin(redirect = '../Auth,login,signup/login.html') {
    if (!this.isLoggedIn()) window.location.href = redirect;
  },
  requireAdmin(redirect = '../Auth,login,signup/login.html') {
    if (!this.isLoggedIn() || this.getUser().role !== 'admin') window.location.href = redirect;
  },
  logout(redirect = '../Auth,login,signup/login.html') {
    localStorage.clear();
    window.location.href = redirect;
  }
};

// Export for legacy script usage
window.StaticAuth = Auth;
