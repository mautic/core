<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Doctrine\Helper\ColumnSchemaHelper;
use Mautic\CoreBundle\Doctrine\Helper\TableSchemaHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\TemplatingHelper;
use Mautic\CoreBundle\Helper\ThemeHelper;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\FormBundle\Collector\MappedObjectCollectorInterface;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\FormRepository;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Mautic\FormBundle\Helper\FormUploader;
use Mautic\FormBundle\Model\ActionModel;
use Mautic\FormBundle\Model\FieldModel;
use Mautic\FormBundle\Model\FormModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldModel as LeadFieldModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\RequestStack;

class FormModelTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject&RequestStack
     */
    private $requestStack;

    /**
     * @var MockObject&TemplatingHelper
     */
    private $templatingHelperMock;

    /**
     * @var MockObject&ThemeHelper
     */
    private $themeHelper;

    /**
     * @var MockObject&ActionModel
     */
    private $formActionModel;

    /**
     * @var MockObject&FieldModel
     */
    private $formFieldModel;

    /**
     * @var MockObject&EventDispatcher
     */
    private $dispatcher;

    /**
     * @var MockObject&Translator
     */
    private $translator;

    /**
     * @var MockObject&EntityManager
     */
    private $entityManager;

    /**
     * @var MockObject&FormUploader
     */
    private $formUploaderMock;

    /**
     * @var MockObject&ColumnSchemaHelper
     */
    private $columnSchemaHelper;

    /**
     * @var MockObject&TableSchemaHelper
     */
    private $tableSchemaHelper;

    /**
     * @var MockObject&FormRepository
     */
    private $formRepository;

    /**
     * @var MockObject&LeadFieldModel
     */
    private $leadFieldModel;

    /**
     * @var MockObject&ContactTracker
     */
    private $contactTracker;

    /**
     * @var MockObject&FormFieldHelper
     */
    private $fieldHelper;

    /**
     * @var MockObject&MappedObjectCollectorInterface
     */
    private $mappedObjectCollector;

    /**
     * @var FormModel
     */
    private $formModel;

    protected function setUp(): void
    {
        $this->requestStack          = $this->createMock(RequestStack::class);
        $this->templatingHelperMock  = $this->createMock(TemplatingHelper::class);
        $this->themeHelper           = $this->createMock(ThemeHelper::class);
        $this->formActionModel       = $this->createMock(ActionModel::class);
        $this->formFieldModel        = $this->createMock(FieldModel::class);
        $this->contactTracker        = $this->createMock(ContactTracker::class);
        $this->fieldHelper           = $this->createMock(FormFieldHelper::class);
        $this->dispatcher            = $this->createMock(EventDispatcher::class);
        $this->translator            = $this->createMock(Translator::class);
        $this->entityManager         = $this->createMock(EntityManager::class);
        $this->formUploaderMock      = $this->createMock(FormUploader::class);
        $this->leadFieldModel        = $this->createMock(LeadFieldModel::class);
        $this->formRepository        = $this->createMock(FormRepository::class);
        $this->columnSchemaHelper    = $this->createMock(ColumnSchemaHelper::class);
        $this->tableSchemaHelper     = $this->createMock(TableSchemaHelper::class);
        $this->mappedObjectCollector = $this->createMock(MappedObjectCollectorInterface::class);
        $coreParametersHelper        = $this->createMock(CoreParametersHelper::class);

        $this->entityManager->expects($this
            ->any())
            ->method('getRepository')
            ->will(
                $this->returnValueMap(
                    [
                        ['MauticFormBundle:Form', $this->formRepository],
                    ]
                )
            );

        $this->formModel = new FormModel(
            $this->requestStack,
            $this->templatingHelperMock,
            $this->themeHelper,
            $this->formActionModel,
            $this->formFieldModel,
            $this->fieldHelper,
            $this->leadFieldModel,
            $this->formUploaderMock,
            $this->contactTracker,
            $this->columnSchemaHelper,
            $this->tableSchemaHelper,
            $this->mappedObjectCollector,
            $coreParametersHelper
        );

        $this->formModel->setDispatcher($this->dispatcher);
        $this->formModel->setTranslator($this->translator);
        $this->formModel->setEntityManager($this->entityManager);
    }

    public function testSetFields(): void
    {
        $form   = new Form();
        $fields = $this->getTestFormFields();
        $this->formModel->setFields($form, $fields);
        $entityFields = $form->getFields()->toArray();

        /** @var Field $newField */
        $newField = $entityFields[array_keys($fields)[0]];

        /** @var Field $fileField */
        $fileField = $entityFields[array_keys($fields)[1]];

        /** @var Field $parentField */
        $parentField = $entityFields[array_keys($fields)[2]];

        /** @var Field $childField */
        $childField = $entityFields[array_keys($fields)[3]];

        /** @var Field $childField */
        $newChildField = $entityFields[array_keys($fields)[4]];

        $this->assertInstanceOf(Field::class, $newField);
        $this->assertSame('email', $newField->getType());
        $this->assertSame('email', $newField->getAlias());
        $this->assertSame(1, $newField->getOrder());
        $this->assertSame('file', $fileField->getType());
        $this->assertSame('file', $fileField->getAlias());
        $this->assertSame(2, $fileField->getOrder());
        $this->assertSame('select', $parentField->getType());
        $this->assertSame('parent', $parentField->getAlias());
        $this->assertSame(3, $parentField->getOrder());
        $this->assertSame('text', $childField->getType());
        $this->assertSame('child', $childField->getAlias());
        $this->assertSame(4, $childField->getOrder());
        $this->assertSame('text', $newChildField->getType());
        $this->assertSame('new_child', $newChildField->getAlias());
        $this->assertSame(4, $newChildField->getOrder());
    }

    public function testGetComponentsFields(): void
    {
        $components = $this->formModel->getCustomComponents();
        $this->assertArrayHasKey('fields', $components);
    }

    public function testGetComponentsActions(): void
    {
        $components = $this->formModel->getCustomComponents();
        $this->assertArrayHasKey('actions', $components);
    }

    public function testGetComponentsValidators(): void
    {
        $components = $this->formModel->getCustomComponents();
        $this->assertArrayHasKey('validators', $components);
    }

    public function testGetEntityForNotFoundContactField(): void
    {
        $formEntity = $this->createMock(Form::class);
        $fields     = new ArrayCollection();
        $formField  = new Field();
        $formField->setMappedField('contactselect');
        $formField->setMappedObject('contact');
        $formField->setProperties(['syncList' => true]);

        $fields->add($formField);

        $formEntity->expects($this->exactly(2))
            ->method('getFields')
            ->willReturn($fields);

        $this->formRepository->expects($this->once())
            ->method('getEntity')
            ->with(5)
            ->willReturn($formEntity);

        $this->leadFieldModel->expects($this->once())
            ->method('getEntityByAlias')
            ->willReturn(null);

        $this->formModel->getEntity(5);

        $this->assertSame(['syncList' => true], $formField->getProperties());
    }

    public function testGetEntityForNotLinkedSelectField(): void
    {
        $formEntity = $this->createMock(Form::class);
        $fields     = new ArrayCollection();
        $formField  = new Field();
        $formField->setProperties(['syncList' => true]);

        $fields->add($formField);

        $formEntity->expects($this->exactly(2))
            ->method('getFields')
            ->willReturn($fields);

        $this->formRepository->expects($this->once())
            ->method('getEntity')
            ->with(5)
            ->willReturn($formEntity);

        $this->leadFieldModel->expects($this->never())
            ->method('getEntityByAlias');

        $this->formModel->getEntity(5);
    }

    public function testGetEntityForNotSyncedSelectField(): void
    {
        $formEntity = $this->createMock(Form::class);
        $fields     = new ArrayCollection();
        $formField  = new Field();
        $formField->setMappedField('contactselect');
        $formField->setMappedObject('contact');
        $formField->setProperties(['syncList' => false]);

        $fields->add($formField);

        $formEntity->expects($this->exactly(2))
            ->method('getFields')
            ->willReturn($fields);

        $this->formRepository->expects($this->once())
            ->method('getEntity')
            ->with(5)
            ->willReturn($formEntity);

        $this->leadFieldModel->expects($this->never())
            ->method('getEntityByAlias');

        $this->formModel->getEntity(5);
    }

    public function testGetEntityForSyncedBooleanFieldFromNotLeadObject(): void
    {
        $formEntity = $this->createMock(Form::class);
        $fields     = new ArrayCollection();
        $options    = ['no' => 'lunch?', 'yes' => 'dinner?'];
        $formField  = new Field();
        $formField->setMappedField('contactbool');
        $formField->setMappedObject('unicorn');
        $formField->setProperties(['syncList' => true]);

        $fields->add($formField);

        $contactField = new LeadField();
        $contactField->setType('boolean');
        $contactField->setProperties($options);

        $formEntity->expects($this->exactly(2))
            ->method('getFields')
            ->willReturn($fields);

        $this->formRepository->expects($this->once())
            ->method('getEntity')
            ->with(5)
            ->willReturn($formEntity);

        $this->leadFieldModel->expects($this->never())
            ->method('getEntityByAlias');

        $this->formModel->getEntity(5);
    }

    public function testGetEntityForSyncedBooleanField(): void
    {
        $formEntity = $this->createMock(Form::class);
        $fields     = new ArrayCollection();
        $options    = ['no' => 'lunch?', 'yes' => 'dinner?'];
        $formField  = new Field();
        $formField->setMappedField('contactbool');
        $formField->setMappedObject('contact');
        $formField->setProperties(['syncList' => true]);

        $fields->add($formField);

        $contactField = new LeadField();
        $contactField->setType('boolean');
        $contactField->setProperties($options);

        $formEntity->expects($this->exactly(2))
            ->method('getFields')
            ->willReturn($fields);

        $this->formRepository->expects($this->once())
            ->method('getEntity')
            ->with(5)
            ->willReturn($formEntity);

        $this->leadFieldModel->expects($this->once())
            ->method('getEntityByAlias')
            ->with('contactbool')
            ->willReturn($contactField);

        $this->formModel->getEntity(5);

        $this->assertSame(['lunch?', 'dinner?'], $formField->getProperties()['list']['list']);
    }

    public function testGetEntityForSyncedCountryField(): void
    {
        $formField = $this->standardSyncListStaticFieldTest('country');

        $this->assertArrayHasKey('Czech Republic', $formField->getProperties()['list']['list']);
    }

    public function testGetEntityForSyncedRegionField(): void
    {
        $formField = $this->standardSyncListStaticFieldTest('region');

        $this->assertArrayHasKey('Canada', $formField->getProperties()['list']['list']);
    }

    public function testGetEntityForSyncedTimezoneField(): void
    {
        $formField = $this->standardSyncListStaticFieldTest('timezone');

        $this->assertArrayHasKey('Africa', $formField->getProperties()['list']['list']);
    }

    public function testGetEntityForSyncedLocaleField(): void
    {
        $formField = $this->standardSyncListStaticFieldTest('locale');

        $this->assertArrayHasKey('Czech (Czechia)', $formField->getProperties()['list']['list']);
    }

    /**
     * @return array<string[]>
     */
    public function fieldTypeProvider(): array
    {
        return [
            ['select'],
            ['multiselect'],
            ['lookup'],
        ];
    }

    /**
     * @dataProvider fieldTypeProvider
     */
    public function testSyncListField(string $type): void
    {
        $formEntity = $this->createMock(Form::class);
        $fields     = new ArrayCollection();
        $options    = [
            ['label' => 'label1', 'value' => 'value1'],
            ['label' => 'label2', 'value' => 'value2'],
        ];

        $formField = new Field();
        $formField->setMappedField('contactfieldalias');
        $formField->setMappedObject('contact');
        $formField->setProperties(['syncList' => true]);

        $contactField = new LeadField();
        $contactField->setType($type);
        $contactField->setProperties(['list' => $options]);

        $fields->add($formField);

        $formEntity->expects($this->exactly(2))
            ->method('getFields')
            ->willReturn($fields);

        $this->formRepository->expects($this->once())
            ->method('getEntity')
            ->with(5)
            ->willReturn($formEntity);

        $this->leadFieldModel->expects($this->once())
            ->method('getEntityByAlias')
            ->with('contactfieldalias')
            ->willReturn($contactField);

        $this->formModel->getEntity(5);

        $this->assertSame($options, $formField->getProperties()['list']['list']);
    }

    private function standardSyncListStaticFieldTest(string $type): Field
    {
        $formEntity = $this->createMock(Form::class);
        $fields     = new ArrayCollection();
        $formField  = new Field();
        $formField->setMappedField('contactfield');
        $formField->setMappedObject('contact');
        $formField->setProperties(['syncList' => true]);

        $fields->add($formField);

        $contactField = new LeadField();
        $contactField->setType($type);

        $formEntity->expects($this->exactly(2))
            ->method('getFields')
            ->willReturn($fields);

        $this->formRepository->expects($this->once())
            ->method('getEntity')
            ->with(5)
            ->willReturn($formEntity);

        $this->leadFieldModel->expects($this->once())
            ->method('getEntityByAlias')
            ->with('contactfield')
            ->willReturn($contactField);

        $this->formModel->getEntity(5);

        return $formField;
    }

    public function testGetContactFieldPropertiesListWhenFieldNotFound(): void
    {
        $this->leadFieldModel->expects($this->once())
            ->method('getEntityByAlias');

        $this->assertNull($this->formModel->getContactFieldPropertiesList('alias_a'));
    }

    public function testGetContactFieldPropertiesListWhenFieldFoundButNotList(): void
    {
        $field = new LeadField();
        $field->setType('text');

        $this->leadFieldModel->expects($this->once())
            ->method('getEntityByAlias')
            ->willReturn($field);

        $this->assertNull($this->formModel->getContactFieldPropertiesList('alias_a'));
    }

    public function testGetContactFieldPropertiesListWhenSelectFieldFound(): void
    {
        $field = new LeadField();
        $field->setType('select');
        $field->setProperties(['list' => ['choice_a' => 'Choice A']]);

        $this->leadFieldModel->expects($this->once())
            ->method('getEntityByAlias')
            ->willReturn($field);

        $this->assertSame(
            ['choice_a' => 'Choice A'],
            $this->formModel->getContactFieldPropertiesList('alias_a')
        );
    }

    public function testPopulateValuesWithLeadWithoutAutofill(): void
    {
        $formHtml   = '<html>';
        $form       = new Form();
        $emailField = new Field();
        $emailField->setMappedField('email');
        $emailField->setMappedObject('contact');
        $emailField->setIsAutoFill(false);
        $form->addField(123, $emailField);

        $this->contactTracker->expects($this->never())
            ->method('getContact');

        $this->formModel->populateValuesWithLead($form, $formHtml);
    }

    public function testPopulateValuesWithLeadWithoutLeadObject(): void
    {
        $formHtml   = '<html>';
        $form       = new Form();
        $emailField = new Field();
        $emailField->setMappedField('email');
        $emailField->setMappedObject('unicorn');
        $emailField->setIsAutoFill(true);
        $form->addField(123, $emailField);

        $this->contactTracker->expects($this->never())
            ->method('getContact');

        $this->formModel->populateValuesWithLead($form, $formHtml);
    }

    public function testPopulateValuesWithLeadWithoutLeadEntity(): void
    {
        $formHtml   = '<html>';
        $form       = new Form();
        $emailField = new Field();
        $emailField->setMappedField('email');
        $emailField->setMappedObject('contact');
        $emailField->setIsAutoFill(true);
        $form->addField(123, $emailField);

        $this->contactTracker->expects($this->once())
            ->method('getContact')
            ->willReturn(null);

        $this->fieldHelper->expects($this->never())
            ->method('populateField');

        $this->formModel->populateValuesWithLead($form, $formHtml);
    }

    public function testPopulateValuesWithLeadWithoutMappedField(): void
    {
        $formHtml   = '<html>';
        $form       = new Form();
        $emailField = new Field();
        $emailField->setIsAutoFill(true);
        $form->addField(123, $emailField);

        $this->contactTracker->expects($this->never())
            ->method('getContact');

        $this->formModel->populateValuesWithLead($form, $formHtml);
    }

    public function testPopulateValuesWithLeadWithEmptyLeadFieldValue(): void
    {
        $formHtml   = '<html>';
        $form       = new Form();
        $emailField = new Field();
        $contact    = new class() extends Lead {
            public function getFieldValue($field, $group = null)
            {
                Assert::assertSame('email', $field);

                return '';
            }
        };
        $emailField->setMappedField('email');
        $emailField->setMappedObject('contact');
        $emailField->setIsAutoFill(true);
        $form->addField(123, $emailField);

        $this->contactTracker->method('getContact')
            ->willReturn($contact);

        $this->fieldHelper->expects($this->never())
            ->method('populateField');

        $this->formModel->populateValuesWithLead($form, $formHtml);
    }

    public function testPopulateValuesWithLead(): void
    {
        $formHtml   = '<html>';
        $form       = new Form();
        $emailField = new Field();
        $contact    = new class() extends Lead {
            public function getFieldValue($field, $group = null)
            {
                Assert::assertSame('email', $field);

                return 'john@doe.email';
            }
        };
        $emailField->setMappedField('email');
        $emailField->setMappedObject('contact');
        $emailField->setIsAutoFill(true);
        $form->addField(123, $emailField);

        $this->contactTracker->method('getContact')
            ->willReturn($contact);

        $this->fieldHelper->expects($this->once())
            ->method('populateField')
            ->with($emailField, 'john@doe.email', 'form-', $formHtml);

        $this->formModel->populateValuesWithLead($form, $formHtml);
    }

    /**
     * @return mixed[]
     */
    private function getTestFormFields(): array
    {
        $fieldSession          = 'mautic_'.sha1(uniqid((string) mt_rand(), true));
        $fieldSession2         = 'mautic_'.sha1(uniqid((string) mt_rand(), true));
        $fields[$fieldSession] = [
            'label'        => 'Email',
            'showLabel'    => 1,
            'saveResult'   => 1,
            'defaultValue' => false,
            'alias'        => 'email',
            'type'         => 'email',
            'mappedField'  => 'email',
            'mappedObject' => 'contact',
            'id'           => $fieldSession,
        ];

        $fields['file'] = [
            'label'                   => 'File',
            'showLabel'               => 1,
            'saveResult'              => 1,
            'defaultValue'            => false,
            'alias'                   => 'file',
            'type'                    => 'file',
            'id'                      => 'file',
            'allowed_file_size'       => 1,
            'allowed_file_extensions' => ['jpg', 'gif'],
        ];

        $fields['123'] = [
            'label'        => 'Parent Field',
            'showLabel'    => 1,
            'saveResult'   => 1,
            'defaultValue' => false,
            'alias'        => 'parent',
            'type'         => 'select',
            'id'           => '123',
        ];

        $fields['456'] = [
            'label'        => 'Child',
            'showLabel'    => 1,
            'saveResult'   => 1,
            'defaultValue' => false,
            'alias'        => 'child',
            'type'         => 'text',
            'id'           => '456',
            'parent'       => '123',
        ];

        $fields[$fieldSession2] = [
            'label'        => 'New Child',
            'showLabel'    => 1,
            'saveResult'   => 1,
            'defaultValue' => false,
            'alias'        => 'new_child',
            'type'         => 'text',
            'id'           => $fieldSession2,
            'parent'       => '123',
        ];

        return $fields;
    }
}
