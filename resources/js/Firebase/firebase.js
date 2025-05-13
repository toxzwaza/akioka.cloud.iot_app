// src/firebase.js
import { initializeApp } from "firebase/app";
import { getMessaging, getToken, onMessage } from "firebase/messaging";

const firebaseConfig = {
    apiKey: "AIzaSyBtzFU6knGwf_MRR_oEspmlsM8_rldj1yQ",
    authDomain: "akioka-cloud-notify-service.firebaseapp.com",
    projectId: "akioka-cloud-notify-service",
    storageBucket: "akioka-cloud-notify-service.firebasestorage.app",
    messagingSenderId: "259507563416",
    appId: "1:259507563416:web:da3687248f3fdeb15119ed",
    measurementId: "G-H8E0FEQQHQ",
    // vapidKey:
    //     "BAFiNQy1EiKe3dMiEdWTWw00FegkQc4uUvoaG8YPCPuAMD86GQPKpZRXkZALHqEsaS7-1R-3xGopdqyflwqGZpg",
};

const app = initializeApp(firebaseConfig);
const messaging = getMessaging(app);

export { messaging, getToken, onMessage };
