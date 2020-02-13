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

/* modules/contrib/blazy/templates/blazy.html.twig */
class __TwigTemplate_047c69a5239542ec8b944ac5de538823b41dda873708c087452a2b598dd880f1 extends \Twig\Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = [
            'blazy_player' => [$this, 'block_blazy_player'],
            'blazy_media' => [$this, 'block_blazy_media'],
            'blazy_content' => [$this, 'block_blazy_content'],
            'blazy_caption' => [$this, 'block_blazy_caption'],
        ];
        $this->sandbox = $this->env->getExtension('\Twig\Extension\SandboxExtension');
        $tags = ["set" => 27, "if" => 47, "block" => 48, "for" => 90];
        $filters = ["clean_class" => 31, "join" => 36, "escape" => 101];
        $functions = [];

        try {
            $this->sandbox->checkSecurity(
                ['set', 'if', 'block', 'for'],
                ['clean_class', 'join', 'escape'],
                []
            );
        } catch (SecurityError $e) {
            $e->setSourceContext($this->getSourceContext());

            if ($e instanceof SecurityNotAllowedTagError && isset($tags[$e->getTagName()])) {
                $e->setTemplateLine($tags[$e->getTagName()]);
            } elseif ($e instanceof SecurityNotAllowedFilterError && isset($filters[$e->getFilterName()])) {
                $e->setTemplateLine($filters[$e->getFilterName()]);
            } elseif ($e instanceof SecurityNotAllowedFunctionError && isset($functions[$e->getFunctionName()])) {
                $e->setTemplateLine($functions[$e->getFunctionName()]);
            }

            throw $e;
        }

    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        // line 27
        $context["classes"] = [0 => "media", 1 => (($this->getAttribute(        // line 29
($context["settings"] ?? null), "namespace", [])) ? (("media--" . $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["settings"] ?? null), "namespace", [])))) : ("")), 2 => ((($this->getAttribute(        // line 30
($context["settings"] ?? null), "lazy", []) && $this->getAttribute(($context["settings"] ?? null), "use_loading", []))) ? ("media--loading") : ("")), 3 => (($this->getAttribute(        // line 31
($context["settings"] ?? null), "media_switch", [])) ? (("media--switch media--switch--" . \Drupal\Component\Utility\Html::getClass($this->sandbox->ensureToStringAllowed($this->getAttribute(($context["settings"] ?? null), "media_switch", []))))) : ("")), 4 => (($this->getAttribute(        // line 32
($context["settings"] ?? null), "player", [])) ? ("media--player") : ("")), 5 => (($this->getAttribute(        // line 33
($context["settings"] ?? null), "ratio", [])) ? (("media--ratio media--ratio--" . $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["settings"] ?? null), "ratio", [])))) : ("")), 6 => (($this->getAttribute(        // line 34
($context["settings"] ?? null), "responsive_image_style_id", [])) ? ("media--responsive") : ("")), 7 => (($this->getAttribute(        // line 35
($context["settings"] ?? null), "type", [])) ? (("media--" . $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["settings"] ?? null), "type", [])))) : ("")), 8 => (($this->getAttribute(        // line 36
($context["settings"] ?? null), "classes", [])) ? (\Drupal\Component\Utility\Html::getClass(twig_join_filter($this->sandbox->ensureToStringAllowed($this->getAttribute(($context["settings"] ?? null), "classes", [])), " "))) : (""))];
        // line 40
        $context["iframe_classes"] = [0 => "media__iframe", 1 => (($this->getAttribute(        // line 42
($context["settings"] ?? null), "ratio", [])) ? ("media__element") : (""))];
        // line 45
        echo "
";
        // line 46
        ob_start(function () { return ''; });
        // line 47
        echo "  ";
        if ($this->getAttribute(($context["settings"] ?? null), "player", [])) {
            // line 48
            echo "    ";
            $this->displayBlock('blazy_player', $context, $blocks);
            // line 55
            echo "  ";
        }
        $context["player"] = ('' === $tmp = ob_get_clean()) ? '' : new Markup($tmp, $this->env->getCharset());
        // line 57
        echo "
";
        // line 58
        ob_start(function () { return ''; });
        // line 59
        echo "  ";
        $this->displayBlock('blazy_media', $context, $blocks);
        $context["media"] = ('' === $tmp = ob_get_clean()) ? '' : new Markup($tmp, $this->env->getCharset());
        // line 67
        echo "
";
        // line 68
        ob_start(function () { return ''; });
        // line 69
        echo "  ";
        $this->displayBlock('blazy_content', $context, $blocks);
        // line 86
        echo "
  ";
        // line 87
        if ((($context["captions"] ?? null) && $this->getAttribute(($context["captions"] ?? null), "inline", [], "any", true, true))) {
            // line 88
            echo "    ";
            $this->displayBlock('blazy_caption', $context, $blocks);
            // line 97
            echo "  ";
        }
        $context["blazy"] = ('' === $tmp = ob_get_clean()) ? '' : new Markup($tmp, $this->env->getCharset());
        // line 99
        echo "
";
        // line 100
        if (($context["wrapper_attributes"] ?? null)) {
            // line 101
            echo "  <div";
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["wrapper_attributes"] ?? null)), "html", null, true);
            echo ">
    ";
            // line 102
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["blazy"] ?? null)), "html", null, true);
            echo "
  </div>
";
        } else {
            // line 105
            echo "  ";
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["blazy"] ?? null)), "html", null, true);
            echo "
";
        }
    }

    // line 48
    public function block_blazy_player($context, array $blocks = [])
    {
        // line 49
        echo "      <iframe";
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["iframe_attributes"] ?? null), "addClass", [0 => ($context["iframe_classes"] ?? null)], "method")), "html", null, true);
        echo " allowfullscreen></iframe>
      ";
        // line 50
        if (($this->getAttribute(($context["settings"] ?? null), "media_switch", []) && $this->getAttribute(($context["settings"] ?? null), "autoplay_url", []))) {
            // line 51
            echo "        <span class=\"media__icon media__icon--close\"></span>
        <span class=\"media__icon media__icon--play\" data-url=\"";
            // line 52
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["settings"] ?? null), "embed_url", [])), "html", null, true);
            echo "\" data-autoplay=\"";
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["settings"] ?? null), "autoplay_url", [])), "html", null, true);
            echo "\"></span>
      ";
        }
        // line 54
        echo "    ";
    }

    // line 59
    public function block_blazy_media($context, array $blocks = [])
    {
        // line 60
        echo "    <div";
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["attributes"] ?? null), "addClass", [0 => ($context["classes"] ?? null)], "method")), "html", null, true);
        echo ">
      ";
        // line 61
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["image"] ?? null)), "html", null, true);
        echo "
      ";
        // line 62
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["player"] ?? null)), "html", null, true);
        echo "
      ";
        // line 63
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["settings"] ?? null), "icon", [])), "html", null, true);
        echo "
    </div>
  ";
    }

    // line 69
    public function block_blazy_content($context, array $blocks = [])
    {
        // line 70
        echo "    ";
        if (($context["media_attributes"] ?? null)) {
            echo "<div";
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["media_attributes"] ?? null)), "html", null, true);
            echo ">";
        }
        // line 71
        echo "      ";
        if ((($context["url"] ?? null) &&  !$this->getAttribute(($context["settings"] ?? null), "player", []))) {
            // line 72
            echo "        <a href=\"";
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["url"] ?? null)), "html", null, true);
            echo "\"";
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["url_attributes"] ?? null)), "html", null, true);
            echo ">";
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["media"] ?? null)), "html", null, true);
            echo "</a>

        ";
            // line 75
            echo "        ";
            if ((($context["captions"] ?? null) &&  !twig_test_empty($this->getAttribute(($context["captions"] ?? null), "lightbox", [])))) {
                // line 76
                echo "          <div class=\"litebox-caption visually-hidden\">";
                // line 77
                echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["captions"] ?? null), "lightbox", [])), "html", null, true);
                // line 78
                echo "</div>
        ";
            }
            // line 80
            echo "
      ";
        } else {
            // line 82
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["media"] ?? null)), "html", null, true);
        }
        // line 84
        echo "    ";
        if (($context["media_attributes"] ?? null)) {
            echo "</div>";
        }
        // line 85
        echo "  ";
    }

    // line 88
    public function block_blazy_caption($context, array $blocks = [])
    {
        // line 89
        echo "      <div";
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["caption_attributes"] ?? null)), "html", null, true);
        echo ">
        ";
        // line 90
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable($this->getAttribute(($context["captions"] ?? null), "inline", []));
        foreach ($context['_seq'] as $context["_key"] => $context["caption"]) {
            // line 91
            echo "          ";
            if ($this->getAttribute($context["caption"], "content", [])) {
                // line 92
                echo "            <";
                echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute($context["caption"], "tag", [])), "html", null, true);
                echo " ";
                echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute($context["caption"], "attributes", [])), "html", null, true);
                echo ">";
                echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute($context["caption"], "content", [])), "html", null, true);
                echo "</";
                echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute($context["caption"], "tag", [])), "html", null, true);
                echo ">
          ";
            }
            // line 94
            echo "        ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['caption'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 95
        echo "      </div>
    ";
    }

    public function getTemplateName()
    {
        return "modules/contrib/blazy/templates/blazy.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  270 => 95,  264 => 94,  252 => 92,  249 => 91,  245 => 90,  240 => 89,  237 => 88,  233 => 85,  228 => 84,  225 => 82,  221 => 80,  217 => 78,  215 => 77,  213 => 76,  210 => 75,  200 => 72,  197 => 71,  190 => 70,  187 => 69,  180 => 63,  176 => 62,  172 => 61,  167 => 60,  164 => 59,  160 => 54,  153 => 52,  150 => 51,  148 => 50,  143 => 49,  140 => 48,  132 => 105,  126 => 102,  121 => 101,  119 => 100,  116 => 99,  112 => 97,  109 => 88,  107 => 87,  104 => 86,  101 => 69,  99 => 68,  96 => 67,  92 => 59,  90 => 58,  87 => 57,  83 => 55,  80 => 48,  77 => 47,  75 => 46,  72 => 45,  70 => 42,  69 => 40,  67 => 36,  66 => 35,  65 => 34,  64 => 33,  63 => 32,  62 => 31,  61 => 30,  60 => 29,  59 => 27,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Source("", "modules/contrib/blazy/templates/blazy.html.twig", "/Applications/MAMP/htdocs/sd38site/web/modules/contrib/blazy/templates/blazy.html.twig");
    }
}
