<?php

namespace App\Admin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;

/**
 * Class FileUploadType
 * @package App\Admin\Form\Type
 */
class FileUploadType extends AbstractType
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * FileUploadType constructor.
     *
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'url_text_field_visible' => false,
            'button_label'           => 'Upload File',
            'uppy'                   => [
                'uppy'      => $this->getUppyConfigDefaults(null),
                'dashboard' => $this->getUppyDashBoardConfigDefaults(null),
                'xhr'       => [
                    'formData' => true,
                ],
            ],
        ]);

        $resolver->setRequired('uppy');
        $resolver->addAllowedTypes('uppy', 'array');
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        $reflection = new \ReflectionClass($view->parent->vars['value']);

        $view->vars['property_path'] = $options['property_path'];
        $view->vars['url_text_field_visible'] = $options['url_text_field_visible'];
        $view->vars['button_label'] = $options['button_label'];

        $options['uppy']['dashboard']['target'] = '#'.$view->vars['id'];
        $options['uppy']['dashboard']['trigger'] = '#'.$view->vars['id'].'_button';
        $options['uppy']['xhr']['formData'] = true;

        $view->vars['uppy']['uppy'] = array_replace_recursive(
            $this->getUppyConfigDefaults($view),
            $options['uppy']['uppy']
        );
        $view->vars['uppy']['dashboard'] = array_replace_recursive(
            $this->getUppyDashBoardConfigDefaults(),
            $options['uppy']['dashboard']
        );

        $view->vars['uppy']['xhr'] = array_replace_recursive([
            'endpoint'   => '/static/images/products',
            'method'     => 'post',
            'formData'   => true,
            'metaFields' => [],
            'bundle'     => false,
            'headers'    => [
                'entity'        => get_class($view->parent->vars['value']),
                'entity-id'     => (string)$view->parent->vars['value']->getId(),
                'property-path' => (string)$options['property_path'],
                'app-id'        => $options['attr']['app_id'] ?? null,
            ],
        ], $options['uppy']['xhr']);

        if (true === $view->vars['uppy']['xhr']['bundle']) {
            $view->vars['uppy']['xhr']['formData'] = true;
        }

        $view->vars['uppy']['dom'] = [
            'target'  => ltrim($options['uppy']['dashboard']['target'], '#'),
            'trigger' => ltrim($options['uppy']['dashboard']['trigger'], '#'),
            'id'      => $view->vars['id'],
        ];

        // If there is a file already.
        $imageUrl = $view->vars['value'];

        if (false !== filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            if (!$data = @file_get_contents($imageUrl)) {
                throw new FileDownloadException('Can not download file from URL: '.$imageUrl);
            }

            $pos = strrpos($imageUrl, '/');
            $fileName = $pos === false ? $imageUrl : substr($imageUrl, $pos + 1);

            $view->vars['uppy']['uppy']['addFile'] = [
                'name'     => $fileName,
                'data'     => \base64_encode($data),
                'type'     => S3ImageStorage::getUrlMimeType($imageUrl),
                'preview'  => $imageUrl,
                'source'   => 'local',
                'isRemote' => true,
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        parent::finishView($view, $form, $options);
    }

    /**
     * @return array
     */
    private function getUppyConfigDefaults(): array
    {
        return [
            'debug'        => false,
            'autoProceed'  => false,
            'restrictions' => [
                'maxFileSize'      => 5242880, // 5MB
                'maxNumberOfFiles' => 5,
                'minNumberOfFiles' => 1,
                'allowedFileTypes' => [
                    'image/*'
                ],
            ],
            'meta'         => [],
            'addFile'      => null,
        ];
    }

    /**
     * @return array
     */
    private function getUppyDashBoardConfigDefaults(): array
    {
        return [
            'trigger'                 => '.UppyModalOpenerBtn',
            'inline'                  => false,
            'target'                  => '#UppyDashboardContainer',
            'replaceTargetContent'    => true,
            'showProgressDetails'     => true,
            'hideProgressAfterFinish' => false,
            'note'                    => '',
            'height'                  => 470,
            'metaFields'              => [],
            'browserBackButtonClose'  => false,
        ];
    }

    public function getBlockPrefix(): string
    {
        return 'file_upload';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(): ?string
    {
        return TextType::class;
    }
}