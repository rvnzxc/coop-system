import './bootstrap';

import Chart from 'chart.js/auto';
window.Chart = Chart;

const downloadSvgAsPng = async (link) => {
    if (link.dataset.downloading === 'true') {
        return;
    }

    link.dataset.downloading = 'true';

    try {
        const response = await fetch(link.href, { credentials: 'same-origin' });

        if (!response.ok) {
            throw new Error('Unable to fetch the image.');
        }

        const svg = await response.text();
        const svgUrl = URL.createObjectURL(new Blob([svg], { type: 'image/svg+xml;charset=utf-8' }));
        const image = new Image();

        await new Promise((resolve, reject) => {
            image.onload = resolve;
            image.onerror = () => reject(new Error('Unable to render the image.'));
            image.src = svgUrl;
        });

        const scale = 3;
        const width = image.naturalWidth || image.width;
        const height = image.naturalHeight || image.height;

        if (!width || !height) {
            throw new Error('The image has no dimensions.');
        }

        const canvas = document.createElement('canvas');
        canvas.width = Math.round(width * scale);
        canvas.height = Math.round(height * scale);

        const context = canvas.getContext('2d');
        context.fillStyle = '#ffffff';
        context.fillRect(0, 0, canvas.width, canvas.height);
        context.drawImage(image, 0, 0, canvas.width, canvas.height);

        const png = await new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));

        if (!png) {
            throw new Error('Unable to create the PNG file.');
        }

        const disposition = response.headers.get('content-disposition') || '';
        const filenameMatch = disposition.match(/filename="?([^";]+)"?/i);
        const filename = (filenameMatch?.[1] || link.dataset.filename || 'download.svg')
            .replace(/\.svg$/i, '.png');

        const downloadUrl = URL.createObjectURL(png);
        const anchor = document.createElement('a');
        anchor.href = downloadUrl;
        anchor.download = filename;
        document.body.appendChild(anchor);
        anchor.click();
        anchor.remove();

        window.setTimeout(() => URL.revokeObjectURL(downloadUrl), 1000);
        URL.revokeObjectURL(svgUrl);
    } catch (error) {
        console.error(error);
        var message = 'Unable to download the image. Please try again.';
        if (typeof window.showToast === 'function') {
            window.showToast(message, 'error');
        } else {
            console.error(message);
        }
    } finally {
        delete link.dataset.downloading;
    }
};

document.addEventListener('click', (event) => {
    const link = event.target.closest('a[data-download-png]');

    if (!link) {
        return;
    }

    event.preventDefault();
    downloadSvgAsPng(link);
});
