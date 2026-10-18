import { expect } from '@playwright/test';

/** Host-mapped Mailpit UI/API for isolated E2E (compose profile `mail` → mailer:8025). */
export function mailpitBaseUrl(): string {
  const port =
    process.env.MAILPIT_UI_PORT ??
    process.env.MAILPIT_HTTP_PORT ??
    process.env.E2E_MAILPIT_UI_PORT ??
    '18027';

  return `http://localhost:${port}`;
}

export type MailpitMessageSummary = {
  ID: string;
  Subject: string;
  To: Array<{ Address?: string; Name?: string }>;
  From?: { Address?: string; Name?: string };
};

type MailpitMessagesResponse = {
  total: number;
  messages: MailpitMessageSummary[];
};

export async function expectMailpitHealthy(): Promise<void> {
  const res = await fetch(`${mailpitBaseUrl()}/api/v1/info`);
  expect(res.ok, `Mailpit health failed at ${mailpitBaseUrl()}`).toBeTruthy();
}

export async function clearMailpitMessages(): Promise<void> {
  const res = await fetch(`${mailpitBaseUrl()}/api/v1/messages`, { method: 'DELETE' });
  expect(res.ok || res.status === 200, `Mailpit DELETE messages failed: ${res.status}`).toBeTruthy();
}

export async function listMailpitMessages(): Promise<MailpitMessagesResponse> {
  const res = await fetch(`${mailpitBaseUrl()}/api/v1/messages`);
  expect(res.ok, `Mailpit list failed: ${res.status}`).toBeTruthy();

  return (await res.json()) as MailpitMessagesResponse;
}

export async function waitForMailpitMessage(options?: {
  timeoutMs?: number;
  toAddress?: string;
  subjectRe?: RegExp;
}): Promise<MailpitMessageSummary> {
  const timeoutMs = options?.timeoutMs ?? 30_000;
  const deadline = Date.now() + timeoutMs;
  let lastTotal = 0;

  while (Date.now() < deadline) {
    const payload = await listMailpitMessages();
    lastTotal = payload.total;
    const match = payload.messages.find((msg) => {
      if (options?.toAddress) {
        const to = (msg.To ?? []).map((t) => (t.Address ?? '').toLowerCase());
        if (!to.includes(options.toAddress.toLowerCase())) {
          return false;
        }
      }
      if (options?.subjectRe && !options.subjectRe.test(msg.Subject ?? '')) {
        return false;
      }

      return true;
    });
    if (match) {
      return match;
    }
    await new Promise((r) => setTimeout(r, 500));
  }

  throw new Error(
    `Mailpit: no matching message within ${timeoutMs}ms (last total=${lastTotal}) at ${mailpitBaseUrl()}`,
  );
}

export async function getMailpitMessageHtml(id: string): Promise<string> {
  const res = await fetch(`${mailpitBaseUrl()}/api/v1/message/${id}`);
  expect(res.ok, `Mailpit message ${id} failed: ${res.status}`).toBeTruthy();
  const body = (await res.json()) as { HTML?: string; Text?: string };

  return body.HTML || body.Text || '';
}
