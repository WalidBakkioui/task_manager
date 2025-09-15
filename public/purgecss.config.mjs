// purgecss.config.mjs
export default {
    content: ['templates/**/*.html.twig', 'public/**/*.js'],
    css: ['public/assets/bootstrap/css/bootstrap.min.css'],
    safelist: [
        { pattern: /^container(-fluid)?$/ }, { pattern: /^row$/ }, { pattern: /^col-/ },
        { pattern: /^g-\d+$/ }, { pattern: /^(m|p)(t|b|s|e|x|y)?-\d+$/ }, { pattern: /^w-\d+$/ },
        { pattern: /^h-\d+$/ }, { pattern: /^d-/ }, { pattern: /^justify-content-/ },
        { pattern: /^align-items-/ }, { pattern: /^gap-/ }, { pattern: /^shadow/ },
        { pattern: /^border(-.*)?$/ }, { pattern: /^rounded(-\d+)?$/ }, { pattern: /^text-/ },
        { pattern: /^bg-/ }, { pattern: /^link-/ },
        { pattern: /^btn/ }, { pattern: /^badge/ }, { pattern: /^card/ }, { pattern: /^alert/ },
        { pattern: /^table/ }, { pattern: /^nav$/ }, { pattern: /^navbar/ }, { pattern: /^dropdown/ },
        { pattern: /^form-control$/ }, { pattern: /^form-select$/ },
        { pattern: /^collapse$/ }, { pattern: /^collapsing$/ }, { pattern: /^show$/ }, { pattern: /^fade$/ },
        { pattern: /^modal$/ }, { pattern: /^offcanvas$/ },
        { pattern: /^bi(-.*)?$/ },
    ],
    output: 'public/assets/bootstrap/css/bootstrap.purged.css'
};