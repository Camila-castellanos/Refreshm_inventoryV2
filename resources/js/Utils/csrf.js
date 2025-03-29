import axios from 'axios';

export async function renewCsrfToken() {
  try {
    const csrfTokenUrl = route ? route('csrf-token') : '/api/csrf-token';
    const response = await axios.get(csrfTokenUrl);
    const newToken = response.data?.csrf_token;

    if (newToken) {
      document.querySelector('meta[name="csrf-token"]').setAttribute('content', newToken);
      axios.defaults.headers.common['X-CSRF-TOKEN'] = newToken;
      return true; // Indicate success
    } else {
      console.error('CSRF token refresh failed: Token is null or undefined');
      return false; // Indicate failure
    }
  } catch (error) {
    console.error('Error fetching new CSRF token:', error);
    return false; // Indicate failure
  }
}
