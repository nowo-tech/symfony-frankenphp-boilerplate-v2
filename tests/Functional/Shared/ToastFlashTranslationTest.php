<?php

declare(strict_types=1);

namespace App\Tests\Functional\Shared;

use App\Tests\Support\DatabaseWebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The toast stack (templates/_toasts.html.twig, UiKit `toasts` theme partial) renders both flash shapes the app
 * uses: plain translation keys (AuthKit |flash_trans) and TranslatableMessage objects (|trans). AuthKit's
 * flash_trans up to 1.27.2 returned objects unchanged, so a TranslatableMessage flash was a 500
 * ("could not be converted to string").
 */
final class ToastFlashTranslationTest extends DatabaseWebTestCase
{
    public function testTranslatableMessageAndPlainKeyFlashesRenderTranslated(): void
    {
        $client = self::createClient();
        $client->request(Request::METHOD_GET, '/login');
        self::assertResponseIsSuccessful();
        $locale = $client->getRequest()->getLocale();

        $session = self::getContainer()->get('session.factory')->createSession();
        \assert($session instanceof FlashBagAwareSessionInterface);
        $session->getFlashBag()->add('success', new TranslatableMessage('flash.groups.created'));
        $session->getFlashBag()->add('error', 'flash.groups.name_required');
        $session->save();
        $client->getCookieJar()->set(new Cookie($session->getName(), $session->getId()));

        $crawler = $client->request(Request::METHOD_GET, '/login');

        self::assertResponseIsSuccessful();
        $translator = self::getContainer()->get(TranslatorInterface::class);
        $created = $translator->trans('flash.groups.created', [], 'messages', $locale);
        $nameRequired = $translator->trans('flash.groups.name_required', [], 'messages', $locale);
        self::assertNotSame('flash.groups.created', $created);
        self::assertSelectorTextContains('.toast-stack .flash-success .flash__message', $created);
        self::assertSelectorTextContains('.toast-stack .flash-error .flash__message', $nameRequired);
        self::assertStringNotContainsString('flash.groups.', $crawler->filter('.toast-stack')->text());
    }
}
