<?php

namespace Mautic\FormBundle\Tests\Controller;

use Mautic\CoreBundle\Helper\LanguageHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;

class FormControllerFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    /**
     * Index should return status code 200.
     */
    public function testIndexActionWhenNotFiltered(): void
    {
        $this->client->request('GET', '/s/forms');
        $this->assertTrue($this->client->getResponse()->isOk());
    }

    /**
     * Filtering should return status code 200.
     */
    public function testIndexActionWhenFiltering(): void
    {
        $this->client->request('GET', '/s/forms?search=has%3Aresults&tmpl=list');
        $this->assertTrue($this->client->getResponse()->isOk());
    }

    /**
     * Get form's create page.
     */
    public function testNewActionForm(): void
    {
        $this->client->request('GET', '/s/forms/new/');
        $this->assertTrue($this->client->getResponse()->isOk());
    }

    /**
     * @see https://github.com/mautic/mautic/issues/10453
     */
    public function testSaveActionForm(): void
    {
        $crawler = $this->client->request('GET', '/s/forms/new/');
        $this->assertTrue($this->client->getResponse()->isOk());

        $form = $crawler->filterXPath('//form[@name="mauticform"]')->form();
        $form->setValues(
            [
                'mauticform[name]'        => 'Test',
                'mauticform[renderStyle]' => '0',
            ]
        );
        $crawler = $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isOk());

        $form = $crawler->filterXPath('//form[@name="mauticform"]')->form();
        $form->setValues(
            [
                'mauticform[renderStyle]' => '0',
            ]
        );

        // The form failed to save when saved for the second time with renderStyle=No.
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());
        $this->assertStringNotContainsString('Internal Server Error - Expected argument of type "null or string", "boolean" given', $this->client->getResponse()->getContent());
    }

    public function testSuccessfulSubmitActionForm(): void
    {
        $crawler = $this->client->request('GET', '/s/forms/new/');
        $this->assertTrue($this->client->getResponse()->isOk());

        $selectedValue = $crawler->filter('#mauticform_postAction option:selected')->attr('value');

        $this->assertEquals('message', $selectedValue);

        $form = $crawler->filterXPath('//form[@name="mauticform"]')->form();
        $form->setValues(
            [
                'mauticform[name]' => 'Test',
            ]
        );
        $crawler = $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isOk());

        $divClass = $crawler->filter('#mauticform_postActionProperty')->parents()->first()->attr('class');

        $this->assertStringContainsString('has-error', $divClass);
    }

    public function testLanguageForm(): void
    {
        $translationsPath = __DIR__.'/resource/language/fr';
        $languagePath     = __DIR__.'/../../../../../translations/fr';
        $filesystem       = new Filesystem();

        // copy all from $translationsPath to $languagePath
        $filesystem->mirror($translationsPath, $languagePath);

        /** @var LanguageHelper $languageHelper */
        $languageHelper = $this->getContainer()->get('mautic.helper.language');

        $formPayload = [
            'name'       => 'Test Form',
            'formType'   => 'campaign',
            'language'   => 'fr',
            'postAction' => 'return',
            'fields'     => [
                [
                    'label'      => 'Email',
                    'alias'      => 'email',
                    'type'       => 'email',
                    'leadField'  => 'email',
                    'isRequired' => true,
                ], [
                    'label' => 'Submit',
                    'alias' => 'submit',
                    'type'  => 'button',
                ],
            ],
        ];
        $this->client->request('POST', '/api/forms/new', $formPayload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $this->assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), json_encode($languageHelper->getLanguageChoices()));
        $form     = $response['form'];
        $formId   = $form['id'];

        $crawler = $this->client->request('GET', '/form/'.$form['id']);
        $this->assertStringContainsString('Merci de patienter...', $crawler->html());
        $this->assertStringContainsString('Ceci est requis.', $crawler->html());

        $filesystem->remove($languagePath);
    }
}
