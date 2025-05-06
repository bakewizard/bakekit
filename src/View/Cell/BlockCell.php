<?php
declare(strict_types=1);

namespace App\View\Cell;

use App\Model\Entity\Block;
use App\View\AppView;
use BadMethodCallException;
use Cake\Cache\Cache;
use Cake\View\Cell;
use Cake\View\Exception\MissingCellTemplateException;
use Cake\View\Exception\MissingTemplateException;
use Cake\View\View;
use Override;
use ReflectionException;
use ReflectionMethod;

/**
 * Block cell
 */
class BlockCell extends Cell
{
    /**
     * @inheritDoc
     */
    protected array $_validCellOptions = ['block', 'parentView'];

    /**
     * Block entity
     *
     * @var \App\Model\Entity\Block
     */
    protected Block $block;

    /**
     * Parent view instance.
     *
     * @var \App\View\AppView
     */
    protected AppView $parentView;

    /**
     * Initialization logic run at the end of object construction.
     *
     * @return void
     */
    #[Override]
    public function initialize(): void
    {
        $this->set(['block' => $this->block ?? null, 'parentView' => $this->parentView ?? null, 'config' => $this->parentView->get('config') ?? null]);
    }

    /**
     * Render the cell.
     *
     * @param string|null $template Custom template name to render. If not provided (null), the last
     * value will be used. This value is automatically set by `CellTrait::cell()`.
     * @return string The rendered cell.
     * @throws \Cake\View\Exception\MissingCellTemplateException
     *   When a MissingTemplateException is raised during rendering.
     */
    #[Override]
    public function render(?string $template = null): string
    {
        $cache = [];
        if ($this->_cache) {
            $cache = $this->_cacheConfig($this->action, $template);
        }

        $render = function () use ($template) {
            try {
                $reflect = new ReflectionMethod($this, $this->action);
                $reflect->invokeArgs($this, $this->args);
            } catch (ReflectionException $e) {
                throw new BadMethodCallException(sprintf(
                    'Class %s does not have a "%s" method.',
                    static::class,
                    $this->action,
                ));
            }

            $builder = $this->viewBuilder();

            if ($template !== null) {
                $builder->setTemplate($template);
            }

            $className = static::class;
            $namePrefix = '\View\Cell\\';
            /** @psalm-suppress PossiblyFalseOperand */
            $name = substr($className, strpos($className, $namePrefix) + strlen($namePrefix));
            $name = substr($name, 0, -4);
            if (!$builder->getTemplatePath()) {
                $builder->setTemplatePath(
                    static::TEMPLATE_FOLDER . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $name),
                );
            }
            $template = $builder->getTemplate();

            $this->View = $this->createView();
            try {
                return $this->View->render($template, false);
            } catch (MissingTemplateException $e) {
                $attributes = $e->getAttributes();
                throw new MissingCellTemplateException(
                    $name,
                    basename($attributes['file']),
                    $attributes['paths'],
                    null,
                    $e,
                );
            }
        };

        if ($cache) {
            return Cache::remember($cache['key'], $render, $cache['config']);
        }

        return $render();
    }

    /**
     * Returns view instance
     *
     * @return \Cake\View\View View instance
     */
    public function getView(): View
    {
        return $this->View;
    }
}
