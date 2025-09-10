export function getImgPath(img_path) {
    return img_path && img_path.includes("storage")
        ? `https://akioka.cloud/${img_path}`
        : img_path;
}

export function changeDateFormat(date) {
    return new Date(date).toLocaleDateString("ja-JP", {
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
    });
}

export function confirmWindowUpdate(message, route) {
    if (confirm(message)) {
        if (!route) {
            window.location.reload();
            return;
        }

        // ルートが設定されている場合そのルートにリダイレクト
        window.location.href = route(route);
    }

}
export function nl2br(text){
    
    if (!text) return "";
    // 文字列型でない場合は文字列に変換
    const textStr = String(text);
    const escapedText = textStr
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
    return escapedText.replace(/\n/g, "<br>");
      
}
