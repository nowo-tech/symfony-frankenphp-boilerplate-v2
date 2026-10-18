import fs from 'node:fs';
import type { Page, PageScreenshotOptions } from '@playwright/test';

/** WebP quality for docs/manual screenshots (UI text stays sharp; ~10x smaller than PNG). */
export const MANUAL_WEBP_QUALITY = 0.86;

/**
 * Viewport screenshot saved as WebP (docs/manual). Playwright only writes PNG / JPEG, so the PNG buffer is
 * re-encoded by Chromium's canvas in a throwaway about:blank page (no CSP, no extra npm / system dependency).
 */
export async function screenshotWebp(
  page: Page,
  filePath: string,
  options: Omit<PageScreenshotOptions, 'path' | 'type'> = {},
  quality: number = MANUAL_WEBP_QUALITY,
): Promise<void> {
  const png = await page.screenshot({ ...options, type: 'png' });
  const encoder = await page.context().newPage();
  try {
    const dataUrl = await encoder.evaluate(
      async ({ b64, q }) => {
        const img = new Image();
        img.src = `data:image/png;base64,${b64}`;
        await img.decode();
        const canvas = document.createElement('canvas');
        canvas.width = img.naturalWidth;
        canvas.height = img.naturalHeight;
        canvas.getContext('2d')!.drawImage(img, 0, 0);
        return canvas.toDataURL('image/webp', q);
      },
      { b64: png.toString('base64'), q: quality },
    );
    if (!dataUrl.startsWith('data:image/webp;base64,')) {
      throw new Error(`Chromium did not encode WebP for ${filePath}`);
    }
    fs.writeFileSync(filePath, Buffer.from(dataUrl.slice('data:image/webp;base64,'.length), 'base64'));
  } finally {
    await encoder.close();
  }
}
