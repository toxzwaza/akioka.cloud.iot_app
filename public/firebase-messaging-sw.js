// public/firebase-messaging-sw.js
importScripts(
    "https://www.gstatic.com/firebasejs/10.12.1/firebase-app-compat.js"
);
importScripts(
    "https://www.gstatic.com/firebasejs/10.12.1/firebase-messaging-compat.js"
);

firebase.initializeApp({
    apiKey: "AIzaSyBtzFU6knGwf_MRR_oEspmlsM8_rldj1yQ",
    authDomain: "akioka-cloud-notify-service.firebaseapp.com",
    projectId: "akioka-cloud-notify-service",
    messagingSenderId: "259507563416",
    appId: "1:259507563416:web:da3687248f3fdeb15119ed",
});

const messaging = firebase.messaging();

// バックグラウンド通知の受信
messaging.onBackgroundMessage(function(payload) {
    console.log('[firebase-messaging-sw.js] バックグラウンド通知を受信:', payload);
  
    const notificationTitle = payload.notification.title;
    const notificationOptions = {
      body: payload.notification.body,
      icon: '/icons/512.png', // 任意のアイコン
    };
  
    self.registration.showNotification(notificationTitle, notificationOptions);
  });