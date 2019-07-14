<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* config/form_display/fieldset_bottom.twig */
class __TwigTemplate_81dfb5fd28631b2ab883ec0cf6028074e6116747f23b971c1c942843ee2901b9 extends \Twig\Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        // line 1
        $context["colspan"] = 2;
        // line 2
        if (($context["is_setup"] ?? null)) {
            // line 3
            echo "    ";
            $context["colspan"] = (($context["colspan"] ?? null) + 1);
        }
        // line 5
        if (($context["show_buttons"] ?? null)) {
            // line 6
            echo "    <tr>
        <td colspan=\"";
            // line 7
            echo twig_escape_filter($this->env, ($context["colspan"] ?? null), "html", null, true);
            echo "\" class=\"lastrow\">
            <input type=\"submit\" name=\"submit_save\" value=\"";
            // line 8
            echo _gettext("Apply");
            echo "\" class=\"green\" />
            <input type=\"button\" name=\"submit_reset\" value=\"";
            // line 9
            echo _gettext("Reset");
            echo "\" />
        </td>
    </tr>
";
        }
        // line 13
        echo "</table>
</fieldset>
";
    }

    public function getTemplateName()
    {
        return "config/form_display/fieldset_bottom.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  58 => 13,  51 => 9,  47 => 8,  43 => 7,  40 => 6,  38 => 5,  34 => 3,  32 => 2,  30 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Source("", "config/form_display/fieldset_bottom.twig", "/vagrant/extensions/phpmyadmin/phpmyadmin/templates/config/form_display/fieldset_bottom.twig");
    }
}
