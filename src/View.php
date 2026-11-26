<?php
declare(strict_types=1);
namespace App;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;

/** Общий рендеринг страниц CostRA. */
final class View
{
    /**
     * Выводит HTML-страницу с фильтрами CostRA.
     *
     * @param string $template Имя внутреннего шаблона.
     * @param array $data Контекст страницы.
     * @return void
     */
    public function render(string $template,array $data): void
    {
        $twig=new Environment(new FilesystemLoader(__DIR__.'/../templates'),['autoescape'=>'html']);
        $twig->addFilter(new TwigFilter('currency',[$this,'formatCurrency']));
        $twig->addFilter(new TwigFilter('local_time',[$this,'localTime']));
        echo $twig->render($template.'.twig',$data);
    }
    /**
     * Переводит время события из UTC в часовой пояс Кишинёва.
     *
     * @param string $timestamp Метка PostgreSQL в UTC.
     * @return string Местное время события с точностью до секунды.
     */
    public function localTime(string $timestamp): string
    {
        $instant = new \DateTimeImmutable($timestamp, new \DateTimeZone('UTC'));
        return $instant->setTimezone(new \DateTimeZone('Europe/Chisinau'))->format('d.m.Y H:i:s');
    }

    /**
     * Форматирует десятичную сумму с разделителями тысяч и обозначением валюты.
     *
     * @param string $amount Десятичное значение из базы.
     * @return string Точное денежное значение в MDL.
     */
    public function formatCurrency(string $amount): string
    {
        [$whole,$fraction]=array_pad(explode('.',ltrim($amount,'-')),2,'00');
        return (str_starts_with($amount,'-')?'-':'').strrev(implode(' ',str_split(strrev($whole),3))).'.'.$fraction.' MDL';
    }
}