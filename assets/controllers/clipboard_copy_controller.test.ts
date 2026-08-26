import { Application } from '@hotwired/stimulus';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import ClipboardCopyController from './clipboard_copy_controller';

describe('clipboard-copy controller', () => {
  let application: Application;

  beforeEach(() => {
    document.body.innerHTML = `
      <button
        type="button"
        data-controller="clipboard-copy"
        data-clipboard-copy-text-value="secret-key"
        data-clipboard-copy-label-value="Copy"
        data-clipboard-copy-done-label-value="Copied"
      >Copy</button>
    `;
    application = Application.start();
    application.register('clipboard-copy', ClipboardCopyController);
    Object.assign(navigator, {
      clipboard: {
        writeText: vi.fn().mockResolvedValue(undefined),
      },
    });
  });

  afterEach(() => {
    application.stop();
    document.body.innerHTML = '';
    vi.restoreAllMocks();
    vi.unstubAllGlobals();
    vi.useRealTimers();
  });

  function clickEvent(target: EventTarget | null): Event {
    const event = new Event('click', { bubbles: true, cancelable: true });
    Object.defineProperty(event, 'currentTarget', { value: target, configurable: true });
    return event;
  }

  async function controllerFor(button: HTMLElement): Promise<ClipboardCopyController> {
    await Promise.resolve();
    return application.getControllerForElementAndIdentifier(
      button,
      'clipboard-copy',
    ) as ClipboardCopyController;
  }

  it('copies text and flashes done label', async () => {
    vi.useFakeTimers({ shouldAdvanceTime: true });
    const button = document.querySelector('button') as HTMLButtonElement;
    const controller = await controllerFor(button);

    await controller.copy(clickEvent(button));
    expect(navigator.clipboard.writeText).toHaveBeenCalledWith('secret-key');
    expect(button.textContent).toBe('Copied');
    expect(button.getAttribute('aria-label')).toBe('Copied');

    await vi.advanceTimersByTimeAsync(1600);
    expect(button.textContent).toBe('Copy');
    expect(button.getAttribute('aria-label')).toBe('Copy');
  });

  it('clears a previous flash timer when copying again', async () => {
    vi.useFakeTimers({ shouldAdvanceTime: true });
    const button = document.querySelector('button') as HTMLButtonElement;
    const controller = await controllerFor(button);

    await controller.copy(clickEvent(button));
    expect(button.textContent).toBe('Copied');
    await controller.copy(clickEvent(button));
    expect(button.textContent).toBe('Copied');

    await vi.advanceTimersByTimeAsync(1600);
    expect(button.textContent).toBe('Copy');
  });

  it('no-ops on empty text', async () => {
    const button = document.querySelector('button') as HTMLButtonElement;
    button.setAttribute('data-clipboard-copy-text-value', '   ');
    application.stop();
    application = Application.start();
    application.register('clipboard-copy', ClipboardCopyController);

    const controller = await controllerFor(button);
    await controller.copy(clickEvent(button));
    expect(navigator.clipboard.writeText).not.toHaveBeenCalled();
  });

  it('copies from URL and flashes done label', async () => {
    vi.useFakeTimers({ shouldAdvanceTime: true });
    const button = document.querySelector('button') as HTMLButtonElement;
    button.removeAttribute('data-clipboard-copy-text-value');
    button.setAttribute('data-clipboard-copy-url-value', '/clipboard.txt');
    application.stop();
    application = Application.start();
    application.register('clipboard-copy', ClipboardCopyController);

    vi.stubGlobal(
      'fetch',
      vi.fn().mockResolvedValue({
        ok: true,
        text: async () => 'fetched-body',
      }),
    );

    const controller = await controllerFor(button);
    await controller.copyFromUrl(clickEvent(button));
    expect(fetch).toHaveBeenCalledWith(
      '/clipboard.txt',
      expect.objectContaining({ credentials: 'same-origin' }),
    );
    expect(navigator.clipboard.writeText).toHaveBeenCalledWith('fetched-body');
    expect(button.textContent).toBe('Copied');

    await vi.advanceTimersByTimeAsync(1600);
    expect(button.textContent).toBe('Copy');
  });

  it('no-ops when URL value is missing or blank', async () => {
    const button = document.querySelector('button') as HTMLButtonElement;
    button.removeAttribute('data-clipboard-copy-text-value');
    application.stop();
    application = Application.start();
    application.register('clipboard-copy', ClipboardCopyController);

    const controller = await controllerFor(button);
    await controller.copyFromUrl(clickEvent(button));
    expect(navigator.clipboard.writeText).not.toHaveBeenCalled();

    button.setAttribute('data-clipboard-copy-url-value', '   ');
    application.stop();
    application = Application.start();
    application.register('clipboard-copy', ClipboardCopyController);
    const again = await controllerFor(button);
    await again.copyFromUrl(clickEvent(button));
    expect(navigator.clipboard.writeText).not.toHaveBeenCalled();
  });

  it('ignores failed or empty URL responses and fetch errors', async () => {
    const button = document.querySelector('button') as HTMLButtonElement;
    button.setAttribute('data-clipboard-copy-url-value', '/clipboard.txt');
    application.stop();
    application = Application.start();
    application.register('clipboard-copy', ClipboardCopyController);
    const controller = await controllerFor(button);

    vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: false, text: async () => 'nope' }));
    await controller.copyFromUrl(clickEvent(button));
    expect(navigator.clipboard.writeText).not.toHaveBeenCalled();

    vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: true, text: async () => '   ' }));
    await controller.copyFromUrl(clickEvent(button));
    expect(navigator.clipboard.writeText).not.toHaveBeenCalled();

    vi.stubGlobal('fetch', vi.fn().mockRejectedValue(new Error('network')));
    await controller.copyFromUrl(clickEvent(button));
    expect(navigator.clipboard.writeText).not.toHaveBeenCalled();
  });

  it('falls back when clipboard API is missing', async () => {
    Object.assign(navigator, { clipboard: undefined });
    const execCommand = vi.fn().mockReturnValue(true);
    document.execCommand = execCommand as typeof document.execCommand;

    const button = document.querySelector('button') as HTMLButtonElement;
    const controller = await controllerFor(button);

    await controller.copy(clickEvent(button));
    expect(execCommand).toHaveBeenCalledWith('copy');
    expect(button.textContent).toBe('Copied');
  });

  it('falls back when writeText rejects', async () => {
    Object.assign(navigator, {
      clipboard: {
        writeText: vi.fn().mockRejectedValue(new Error('denied')),
      },
    });
    const execCommand = vi.fn().mockReturnValue(true);
    document.execCommand = execCommand as typeof document.execCommand;

    const button = document.querySelector('button') as HTMLButtonElement;
    const controller = await controllerFor(button);

    await controller.copy(clickEvent(button));
    expect(execCommand).toHaveBeenCalledWith('copy');
    expect(button.textContent).toBe('Copied');
  });

  it('swallows total clipboard failure', async () => {
    Object.assign(navigator, {
      clipboard: {
        writeText: vi.fn().mockRejectedValue(new Error('denied')),
      },
    });
    document.execCommand = vi.fn(() => {
      throw new Error('exec failed');
    }) as typeof document.execCommand;

    const button = document.querySelector('button') as HTMLButtonElement;
    const controller = await controllerFor(button);

    await controller.copy(clickEvent(button));
    expect(button.textContent).toBe('Copy');
  });

  it('skips flash when currentTarget is not an element', async () => {
    const button = document.querySelector('button') as HTMLButtonElement;
    const controller = await controllerFor(button);

    await controller.copy(clickEvent(null));
    expect(navigator.clipboard.writeText).toHaveBeenCalledWith('secret-key');
    expect(button.textContent).toBe('Copy');
  });

  it('clears pending reset timer on disconnect', async () => {
    vi.useFakeTimers({ shouldAdvanceTime: true });
    const button = document.querySelector('button') as HTMLButtonElement;
    const controller = await controllerFor(button);

    await controller.copy(clickEvent(button));
    expect(button.textContent).toBe('Copied');
    controller.disconnect();
    await vi.advanceTimersByTimeAsync(1600);
    expect(button.textContent).toBe('Copied');
  });
});
