<?php

namespace Mautic\CoreBundle\Twig\Helper;

use Symfony\Component\Form\FormView;

//@mabumusa1 Remove this helpre and replace with twig functions
/**
 * final class FormHelper.
 */
final class FormHelper
{
    /**
     * Render widget if it exists.
     *
     * @param array<string, mixed> $form
     * @param string               $key
     * @param string|null          $template
     * @param array<string,mixed>  $variables
     *
     * @return mixed|string
     */
    public function widgetIfExists($form, $key, $template = null, $variables = [])
    {
        $content = (isset($form[$key])) ? $this->widget($form[$key], $variables) : '';

        if ($content && !empty($template)) {
            $content = str_replace('{content}', $content, $template);
        }

        return $content;
    }

    /**
     * Render row if it exists.
     *
     * @param FormView|array<string> $form
     * @param mixed[]                $variables
     *
     * @return string
     */
    public function rowIfExists($form, string $key, string $template = null, array $variables = [])
    {
        $content = (isset($form[$key])) ? $this->row($form[$key], $variables) : '';

        if ($content && !empty($template)) {
            $content = str_replace('{content}', $content, $template);
        }

        return $content;
    }

    /**
     * Render label if it exists.
     *
     * @param       $form
     * @param       $key
     * @param null  $template
     * @param array $variables
     *
     * @return mixed|string
     */
    public function labelIfExists($form, $key, $template = null, $variables = [])
    {
        $content = (isset($form[$key])) ? $this->label($form[$key], null, $variables) : '';

        if ($content && !empty($template)) {
            $content = str_replace('{content}', $content, $template);
        }

        return $content;
    }

    /**
     * Checks to see if the form and its children has an error.
     *
     * @return bool
     */
    public function containsErrors(FormView $form, array $exluding = [])
    {
        if (count($form->vars['errors'])) {
            return true;
        }
        foreach ($form->children as $key => $child) {
            if (in_array($key, $exluding)) {
                continue;
            }

            if (isset($child->vars['errors']) && count($child->vars['errors'])) {
                return true;
            }

            if (count($child->children)) {
                $hasErrors = $this->containsErrors($child);
                if ($hasErrors) {
                    return true;
                }
            }
        }

        return false;
    }
}
