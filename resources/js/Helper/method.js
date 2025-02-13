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
