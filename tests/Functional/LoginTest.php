<?php
declare(strict_types=1);
namespace App\Tests\Functional;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class LoginTest
 * @package App\Tests\Functional
 * @author Josue Tchirktema <tchirktemajosue@gmail.com>
 * @group legacy
 */
class LoginTest extends WebTestCase
{
    /**
     * @dataProvider provideValidCredentials
     * @param string $email
     * @param string $password
     */
    public function testIfLoginIsSuccessful(string $email, string $password) : void
    {
        $client = static::createClient();
        
        /** @var RouterInterface $router */
        $router = $client->getContainer()->get('router');

        $crawler = $client->request(
            Request::METHOD_GET,
            $router->generate('security_login')
        );

        $form = $crawler->filter("form[name=login]")->form([
            "email" => $email,
            "password" => $password
        ]);
        
        $client->submit($form);

        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
        $client->followRedirect();
        $this->assertRouteSame('index');
    }

    /**
     * @dataProvider provideInvalidCredentials
     * @param string $email
     * @param string $password
     * @param string $errorMessage
     */
    public function testIfCredentialsAreInvalid(string $email, string $password, string $errorMessage): void
    {

        $client = static::createClient();

        /** @var RouterInterface $router */
        $router = $client->getContainer()->get('router');

        $crawler = $client->request(
            Request::METHOD_GET,
            $router->generate('security_login')
        );

        $form = $crawler->filter("form[name=login]")->form([
            "email" => $email,
            "password" => $password
        ]);

        $client->submit($form);

        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
        $client->followRedirect();
        $this->assertSelectorTextContains('form[name=login] > div.alert', $errorMessage);
    }


    public function testIfCsrfTokenIsInvalid(): void
    {

        $client = static::createClient();

        /** @var RouterInterface $router */
        $router = $client->getContainer()->get('router');

        $crawler = $client->request(
            Request::METHOD_GET,
            $router->generate('security_login')
        );

        $form = $crawler->filter("form[name=login]")->form([
            "_csrf_token" => "fail",
            "email" => 'email@email.com',
            "password" => 'password'
        ]);

        $client->submit($form);

        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
        $client->followRedirect();
        $this->assertSelectorTextContains('form[name=login] > div.alert', 'Invalid CSRF token.');
    }

    public function testIfAccountIsSuspend(): void
    {
        $client = static::createClient();
        /** @var RouterInterface $router */
        $router = $client->getContainer()->get('router');
        $crawler = $client->request(
            Request::METHOD_GET,
            $router->generate('security_login')
        );
        $form = $crawler->filter("form[name=login]")->form([
            "email" => 'user2@email.com',
            "password" => 'password'
        ]);
        $client->submit($form);
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
        $client->followRedirect();
        $this->assertSelectorTextContains(
            "form[name=login] > div.alert",
            "Your account is suspended."
        );
    }

    public function provideInvalidCredentials(): iterable
    {
        yield ["fail@email.com","password","Email could not be found."];
        yield ["admin@email.com","fail","Invalid credentials."];
    }

    public function provideValidCredentials(): iterable
    {
        yield ["admin@email.com","password"];
        yield ["user@email.com","password"];
    }
}
