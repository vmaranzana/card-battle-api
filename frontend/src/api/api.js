import axios from 'axios';

export const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL, // asegurate de tener esta variable en `.env`
  headers: {
    'Content-Type': 'application/json',
  },
});
    