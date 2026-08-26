import { Application } from '@hotwired/stimulus';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import CollapsePanelController from './collapse_panel_controller';

describe('collapse-panel controller', () => {
  let application: Application;

  function mount(id = 'raw', withIcon = true): void {
    document.body.innerHTML = `
      <div data-controller="collapse-panel" data-collapse-panel-id-value="${id}">
        <button type="button" data-collapse-panel-target="button" aria-expanded="true"></button>
        ${withIcon ? '<span data-collapse-panel-target="icon"></span>' : ''}
        <div data-collapse-panel-target="body">content</div>
      </div>
    `;
    application = Application.start();
    application.register('collapse-panel', CollapsePanelController);
  }

  beforeEach(() => {
    localStorage.clear();
    window.__APP_ISSUE_PANEL_DEFAULTS__ = ['raw'];
    mount();
  });

  afterEach(() => {
    application.stop();
    document.body.innerHTML = '';
    localStorage.clear();
    delete window.__APP_ISSUE_PANEL_DEFAULTS__;
    vi.restoreAllMocks();
  });

  it('starts collapsed when id is in defaults', async () => {
    await Promise.resolve();
    const root = document.querySelector('[data-controller="collapse-panel"]') as HTMLElement;
    expect(root.classList.contains('is-collapsed')).toBe(true);
    const body = root.querySelector('[data-collapse-panel-target="body"]') as HTMLElement;
    expect(body.hidden).toBe(true);
  });

  it('toggles open state and persists', async () => {
    await Promise.resolve();
    const root = document.querySelector('[data-controller="collapse-panel"]') as HTMLElement;
    const controller = application.getControllerForElementAndIdentifier(
      root,
      'collapse-panel',
    ) as CollapsePanelController;

    controller.toggle();
    expect(root.classList.contains('is-collapsed')).toBe(false);
    const saved = JSON.parse(localStorage.getItem('shell.panelState') ?? '{}');
    expect(saved.raw).toBe(true);
  });

  it('restores boolean state from localStorage', async () => {
    application.stop();
    localStorage.setItem('shell.panelState', JSON.stringify({ raw: true }));
    mount();
    await Promise.resolve();

    const root = document.querySelector('[data-controller="collapse-panel"]') as HTMLElement;
    expect(root.classList.contains('is-collapsed')).toBe(false);
  });

  it('defaults open when id is not listed in defaults', async () => {
    application.stop();
    delete window.__APP_ISSUE_PANEL_DEFAULTS__;
    mount('other');
    await Promise.resolve();

    const root = document.querySelector('[data-controller="collapse-panel"]') as HTMLElement;
    expect(root.classList.contains('is-collapsed')).toBe(false);
  });

  it('ignores invalid localStorage payloads', async () => {
    application.stop();
    localStorage.setItem('shell.panelState', JSON.stringify(['not-an-object']));
    mount();
    await Promise.resolve();
    const root = document.querySelector('[data-controller="collapse-panel"]') as HTMLElement;
    expect(root.classList.contains('is-collapsed')).toBe(true);

    application.stop();
    localStorage.setItem('shell.panelState', '{bad-json');
    mount();
    await Promise.resolve();
    const again = document.querySelector('[data-controller="collapse-panel"]') as HTMLElement;
    expect(again.classList.contains('is-collapsed')).toBe(true);
  });

  it('ignores localStorage write failures', async () => {
    await Promise.resolve();
    const root = document.querySelector('[data-controller="collapse-panel"]') as HTMLElement;
    const controller = application.getControllerForElementAndIdentifier(
      root,
      'collapse-panel',
    ) as CollapsePanelController;

    vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => {
      throw new Error('quota');
    });
    controller.toggle();
    expect(root.classList.contains('is-collapsed')).toBe(false);
  });

  it('works without an icon target', async () => {
    application.stop();
    mount('raw', false);
    await Promise.resolve();
    const root = document.querySelector('[data-controller="collapse-panel"]') as HTMLElement;
    const controller = application.getControllerForElementAndIdentifier(
      root,
      'collapse-panel',
    ) as CollapsePanelController;
    controller.toggle();
    expect(root.classList.contains('is-collapsed')).toBe(false);
  });
});
