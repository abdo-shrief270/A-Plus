<?php

namespace Database\Seeders;

use App\Models\LatexFormat;
use Illuminate\Database\Seeder;

class LatexFormatSeeder extends Seeder
{
    public function run(): void
    {
        $formats = [
            // === الكسور ===
            ['key' => 'fraction',       'name' => 'كسر',         'category' => 'الكسور', 'icon' => '\\(\\frac{أ}{ب}\\)', 'inputs' => [['k'=>'n','l'=>'البسط','p'=>'١'],['k'=>'d','l'=>'المقام','p'=>'٢']], 'template' => '\\frac{%n%}{%d%}', 'sort_order' => 1],
            ['key' => 'nestedFraction', 'name' => 'كسر مركب',    'category' => 'الكسور', 'icon' => '\\(\\frac{\\frac{أ}{ب}}{\\frac{ج}{د}}\\)', 'inputs' => [['k'=>'a','l'=>'بسط البسط','p'=>'١'],['k'=>'b','l'=>'مقام البسط','p'=>'٢'],['k'=>'c','l'=>'بسط المقام','p'=>'٣'],['k'=>'d','l'=>'مقام المقام','p'=>'٤']], 'template' => '\\frac{\\frac{%a%}{%b%}}{\\frac{%c%}{%d%}}', 'sort_order' => 2],
            ['key' => 'mixedNumber',    'name' => 'عدد كسري',    'category' => 'الكسور', 'icon' => '\\(٢\\frac{١}{٣}\\)', 'inputs' => [['k'=>'w','l'=>'الصحيح','p'=>'٢'],['k'=>'n','l'=>'البسط','p'=>'١'],['k'=>'d','l'=>'المقام','p'=>'٣']], 'template' => '%w%\\frac{%n%}{%d%}', 'sort_order' => 3],
            ['key' => 'fractionAdd',    'name' => 'جمع كسور',    'category' => 'الكسور', 'icon' => '\\(\\frac{أ}{ب}+\\frac{ج}{د}\\)', 'inputs' => [['k'=>'a','l'=>'بسط ١','p'=>'١'],['k'=>'b','l'=>'مقام ١','p'=>'٢'],['k'=>'c','l'=>'بسط ٢','p'=>'٣'],['k'=>'d','l'=>'مقام ٢','p'=>'٤']], 'template' => '\\frac{%a%}{%b%}+\\frac{%c%}{%d%}', 'sort_order' => 4],
            ['key' => 'fractionMul',    'name' => 'ضرب كسور',    'category' => 'الكسور', 'icon' => '\\(\\frac{أ}{ب}\\times\\frac{ج}{د}\\)', 'inputs' => [['k'=>'a','l'=>'بسط ١','p'=>'١'],['k'=>'b','l'=>'مقام ١','p'=>'٢'],['k'=>'c','l'=>'بسط ٢','p'=>'٣'],['k'=>'d','l'=>'مقام ٢','p'=>'٤']], 'template' => '\\frac{%a%}{%b%}\\times\\frac{%c%}{%d%}', 'sort_order' => 5],

            // === الجذور والأسس ===
            ['key' => 'sqrt',       'name' => 'جذر تربيعي',     'category' => 'الجذور والأسس', 'icon' => '\\(\\sqrt{س}\\)', 'inputs' => [['k'=>'v','l'=>'القيمة','p'=>'١٦']], 'template' => '\\sqrt{%v%}', 'sort_order' => 10],
            ['key' => 'nthRoot',    'name' => 'جذر نوني',       'category' => 'الجذور والأسس', 'icon' => '\\(\\sqrt[ن]{س}\\)', 'inputs' => [['k'=>'n','l'=>'الدرجة','p'=>'٣'],['k'=>'v','l'=>'القيمة','p'=>'٢٧']], 'template' => '\\sqrt[%n%]{%v%}', 'sort_order' => 11],
            ['key' => 'power',      'name' => 'أس',             'category' => 'الجذور والأسس', 'icon' => '\\(س^{ن}\\)', 'inputs' => [['k'=>'b','l'=>'الأساس','p'=>'س'],['k'=>'e','l'=>'الأس','p'=>'٢']], 'template' => '%b%^{%e%}', 'sort_order' => 12],
            ['key' => 'powerFrac',  'name' => 'أس كسري',        'category' => 'الجذور والأسس', 'icon' => '\\(س^{\\frac{أ}{ب}}\\)', 'inputs' => [['k'=>'b','l'=>'الأساس','p'=>'س'],['k'=>'n','l'=>'بسط الأس','p'=>'١'],['k'=>'d','l'=>'مقام الأس','p'=>'٢']], 'template' => '%b%^{\\frac{%n%}{%d%}}', 'sort_order' => 13],
            ['key' => 'subscript',  'name' => 'دليل سفلي',      'category' => 'الجذور والأسس', 'icon' => '\\(س_{ن}\\)', 'inputs' => [['k'=>'b','l'=>'الرمز','p'=>'س'],['k'=>'s','l'=>'الدليل','p'=>'١']], 'template' => '%b%_{%s%}', 'sort_order' => 14],
            ['key' => 'superSub',   'name' => 'أس ودليل معاً',  'category' => 'الجذور والأسس', 'icon' => '\\(س_{ن}^{م}\\)', 'inputs' => [['k'=>'b','l'=>'الرمز','p'=>'س'],['k'=>'s','l'=>'الدليل','p'=>'ن'],['k'=>'e','l'=>'الأس','p'=>'م']], 'template' => '%b%_{%s%}^{%e%}', 'sort_order' => 15],

            // === العمليات الحسابية ===
            ['key' => 'multiplication', 'name' => 'ضرب',          'category' => 'العمليات الحسابية', 'icon' => '\\(أ \\times ب\\)', 'inputs' => [['k'=>'a','l'=>'الأول','p'=>'٣'],['k'=>'b','l'=>'الثاني','p'=>'٤']], 'template' => '%a% \\times %b%', 'sort_order' => 20],
            ['key' => 'division',       'name' => 'قسمة',         'category' => 'العمليات الحسابية', 'icon' => '\\(أ \\div ب\\)', 'inputs' => [['k'=>'a','l'=>'المقسوم','p'=>'٦'],['k'=>'b','l'=>'المقسوم عليه','p'=>'٢']], 'template' => '%a% \\div %b%', 'sort_order' => 21],
            ['key' => 'plusMinus',       'name' => 'موجب/سالب',    'category' => 'العمليات الحسابية', 'icon' => '\\(أ \\pm ب\\)', 'inputs' => [['k'=>'a','l'=>'الأول','p'=>'٥'],['k'=>'b','l'=>'الثاني','p'=>'٣']], 'template' => '%a% \\pm %b%', 'sort_order' => 22],
            ['key' => 'comparison',     'name' => 'مقارنة',       'category' => 'العمليات الحسابية', 'icon' => '\\(أ \\geq ب\\)', 'inputs' => [['k'=>'a','l'=>'الأيمن','p'=>'س'],['k'=>'op','l'=>'العملية (> < >= <= = !=)','p'=>'>='],['k'=>'b','l'=>'الأيسر','p'=>'٥']], 'template' => '%a% %op% %b%', 'sort_order' => 23],
            ['key' => 'proportional',   'name' => 'تناسب',        'category' => 'العمليات الحسابية', 'icon' => '\\(أ \\propto ب\\)', 'inputs' => [['k'=>'a','l'=>'الأول','p'=>'ص'],['k'=>'b','l'=>'الثاني','p'=>'س']], 'template' => '%a% \\propto %b%', 'sort_order' => 24],
            ['key' => 'approx',         'name' => 'تقريباً',      'category' => 'العمليات الحسابية', 'icon' => '\\(أ \\approx ب\\)', 'inputs' => [['k'=>'a','l'=>'القيمة','p'=>'\\pi'],['k'=>'b','l'=>'التقريب','p'=>'٣.١٤']], 'template' => '%a% \\approx %b%', 'sort_order' => 25],

            // === التحليل ===
            ['key' => 'sum',            'name' => 'مجموع',         'category' => 'التحليل', 'icon' => '\\(\\sum_{ي=١}^{ن}\\)', 'inputs' => [['k'=>'v','l'=>'المتغير','p'=>'ي'],['k'=>'f','l'=>'من','p'=>'١'],['k'=>'t','l'=>'إلى','p'=>'ن'],['k'=>'e','l'=>'التعبير','p'=>'ي']], 'template' => '\\sum_{%v%=%f%}^{%t%} %e%', 'sort_order' => 30],
            ['key' => 'product',        'name' => 'جداء',          'category' => 'التحليل', 'icon' => '\\(\\prod_{ي=١}^{ن}\\)', 'inputs' => [['k'=>'v','l'=>'المتغير','p'=>'ي'],['k'=>'f','l'=>'من','p'=>'١'],['k'=>'t','l'=>'إلى','p'=>'ن'],['k'=>'e','l'=>'التعبير','p'=>'ي']], 'template' => '\\prod_{%v%=%f%}^{%t%} %e%', 'sort_order' => 31],
            ['key' => 'integral',       'name' => 'تكامل',         'category' => 'التحليل', 'icon' => '\\(\\int_{أ}^{ب}\\)', 'inputs' => [['k'=>'f','l'=>'الحد الأدنى','p'=>'٠'],['k'=>'t','l'=>'الحد الأعلى','p'=>'١'],['k'=>'e','l'=>'التعبير','p'=>'س'],['k'=>'d','l'=>'المتغير','p'=>'س']], 'template' => '\\int_{%f%}^{%t%} %e%\\, d%d%', 'sort_order' => 32],
            ['key' => 'doubleIntegral', 'name' => 'تكامل ثنائي',   'category' => 'التحليل', 'icon' => '\\(\\iint\\)', 'inputs' => [['k'=>'e','l'=>'التعبير','p'=>'ف(س,ص)'],['k'=>'d','l'=>'المتغيرات','p'=>'س\\, دص']], 'template' => '\\iint %e%\\, د%d%', 'sort_order' => 33],
            ['key' => 'tripleIntegral', 'name' => 'تكامل ثلاثي',   'category' => 'التحليل', 'icon' => '\\(\\iiint\\)', 'inputs' => [['k'=>'e','l'=>'التعبير','p'=>'ف(س,ص,ع)'],['k'=>'d','l'=>'المتغيرات','p'=>'س\\, دص\\, دع']], 'template' => '\\iiint %e%\\, د%d%', 'sort_order' => 34],
            ['key' => 'limit',          'name' => 'نهاية',         'category' => 'التحليل', 'icon' => '\\(\\lim_{س \\to \\infty}\\)', 'inputs' => [['k'=>'v','l'=>'المتغير','p'=>'س'],['k'=>'t','l'=>'يقترب من','p'=>'\\infty'],['k'=>'e','l'=>'التعبير','p'=>'\\frac{١}{س}']], 'template' => '\\lim_{%v% \\to %t%} %e%', 'sort_order' => 35],
            ['key' => 'derivative',     'name' => 'مشتقة',         'category' => 'التحليل', 'icon' => '\\(\\frac{دص}{دس}\\)', 'inputs' => [['k'=>'n','l'=>'الدالة','p'=>'ص'],['k'=>'d','l'=>'المتغير','p'=>'س']], 'template' => '\\frac{d%n%}{d%d%}', 'sort_order' => 36],
            ['key' => 'partialDeriv',   'name' => 'مشتقة جزئية',   'category' => 'التحليل', 'icon' => '\\(\\frac{\\partial ف}{\\partial س}\\)', 'inputs' => [['k'=>'n','l'=>'الدالة','p'=>'ف'],['k'=>'d','l'=>'المتغير','p'=>'س']], 'template' => '\\frac{\\partial %n%}{\\partial %d%}', 'sort_order' => 37],

            // === اللوغاريتمات والأسية ===
            ['key' => 'log',        'name' => 'لوغاريتم',          'category' => 'اللوغاريتمات والأسية', 'icon' => '\\(\\log_{أ} ب\\)', 'inputs' => [['k'=>'b','l'=>'الأساس','p'=>'٢'],['k'=>'v','l'=>'القيمة','p'=>'٨']], 'template' => '\\log_{%b%} %v%', 'sort_order' => 40],
            ['key' => 'ln',         'name' => 'لوغاريتم طبيعي',    'category' => 'اللوغاريتمات والأسية', 'icon' => '\\(\\ln س\\)', 'inputs' => [['k'=>'v','l'=>'القيمة','p'=>'س']], 'template' => '\\ln %v%', 'sort_order' => 41],
            ['key' => 'naturalExp', 'name' => 'الدالة الأسية',     'category' => 'اللوغاريتمات والأسية', 'icon' => '\\(e^{س}\\)', 'inputs' => [['k'=>'e','l'=>'الأس','p'=>'س']], 'template' => 'e^{%e%}', 'sort_order' => 42],

            // === الدوال المثلثية ===
            ['key' => 'sin',    'name' => 'جيب',              'category' => 'الدوال المثلثية', 'icon' => '\\(\\sin\\theta\\)', 'inputs' => [['k'=>'v','l'=>'الزاوية','p'=>'\\theta']], 'template' => '\\sin %v%', 'sort_order' => 50],
            ['key' => 'cos',    'name' => 'جيب تمام',          'category' => 'الدوال المثلثية', 'icon' => '\\(\\cos\\theta\\)', 'inputs' => [['k'=>'v','l'=>'الزاوية','p'=>'\\theta']], 'template' => '\\cos %v%', 'sort_order' => 51],
            ['key' => 'tan',    'name' => 'ظل',               'category' => 'الدوال المثلثية', 'icon' => '\\(\\tan\\theta\\)', 'inputs' => [['k'=>'v','l'=>'الزاوية','p'=>'\\theta']], 'template' => '\\tan %v%', 'sort_order' => 52],
            ['key' => 'cot',    'name' => 'ظل تمام',           'category' => 'الدوال المثلثية', 'icon' => '\\(\\cot\\theta\\)', 'inputs' => [['k'=>'v','l'=>'الزاوية','p'=>'\\theta']], 'template' => '\\cot %v%', 'sort_order' => 53],
            ['key' => 'sec',    'name' => 'قاطع',              'category' => 'الدوال المثلثية', 'icon' => '\\(\\sec\\theta\\)', 'inputs' => [['k'=>'v','l'=>'الزاوية','p'=>'\\theta']], 'template' => '\\sec %v%', 'sort_order' => 54],
            ['key' => 'csc',    'name' => 'قاطع تمام',          'category' => 'الدوال المثلثية', 'icon' => '\\(\\csc\\theta\\)', 'inputs' => [['k'=>'v','l'=>'الزاوية','p'=>'\\theta']], 'template' => '\\csc %v%', 'sort_order' => 55],
            ['key' => 'arcsin', 'name' => 'جيب عكسي',          'category' => 'الدوال المثلثية', 'icon' => '\\(\\arcsin س\\)', 'inputs' => [['k'=>'v','l'=>'القيمة','p'=>'س']], 'template' => '\\arcsin %v%', 'sort_order' => 56],
            ['key' => 'arccos', 'name' => 'جيب تمام عكسي',     'category' => 'الدوال المثلثية', 'icon' => '\\(\\arccos س\\)', 'inputs' => [['k'=>'v','l'=>'القيمة','p'=>'س']], 'template' => '\\arccos %v%', 'sort_order' => 57],
            ['key' => 'arctan', 'name' => 'ظل عكسي',           'category' => 'الدوال المثلثية', 'icon' => '\\(\\arctan س\\)', 'inputs' => [['k'=>'v','l'=>'القيمة','p'=>'س']], 'template' => '\\arctan %v%', 'sort_order' => 58],

            // === المصفوفات والمتجهات ===
            ['key' => 'matrix2x2',  'name' => 'مصفوفة ٢×٢', 'category' => 'المصفوفات والمتجهات', 'icon' => '\\(\\begin{pmatrix}أ&ب\\\\ج&د\\end{pmatrix}\\)', 'inputs' => [['k'=>'a','l'=>'(١,١)','p'=>'أ'],['k'=>'b','l'=>'(١,٢)','p'=>'ب'],['k'=>'c','l'=>'(٢,١)','p'=>'ج'],['k'=>'d','l'=>'(٢,٢)','p'=>'د']], 'template' => '\\begin{pmatrix} %a% & %b% \\\\ %c% & %d% \\end{pmatrix}', 'sort_order' => 60],
            ['key' => 'matrix3x3',  'name' => 'مصفوفة ٣×٣', 'category' => 'المصفوفات والمتجهات', 'icon' => '\\(\\begin{pmatrix}\\cdot&\\cdot&\\cdot\\\\\\cdot&\\cdot&\\cdot\\\\\\cdot&\\cdot&\\cdot\\end{pmatrix}\\)', 'inputs' => [['k'=>'a','l'=>'(١,١)','p'=>'١'],['k'=>'b','l'=>'(١,٢)','p'=>'٠'],['k'=>'c','l'=>'(١,٣)','p'=>'٠'],['k'=>'d','l'=>'(٢,١)','p'=>'٠'],['k'=>'e','l'=>'(٢,٢)','p'=>'١'],['k'=>'f','l'=>'(٢,٣)','p'=>'٠'],['k'=>'g','l'=>'(٣,١)','p'=>'٠'],['k'=>'h','l'=>'(٣,٢)','p'=>'٠'],['k'=>'i','l'=>'(٣,٣)','p'=>'١']], 'template' => '\\begin{pmatrix} %a% & %b% & %c% \\\\ %d% & %e% & %f% \\\\ %g% & %h% & %i% \\end{pmatrix}', 'sort_order' => 61],
            ['key' => 'determinant', 'name' => 'محدد',       'category' => 'المصفوفات والمتجهات', 'icon' => '\\(\\begin{vmatrix}أ&ب\\\\ج&د\\end{vmatrix}\\)', 'inputs' => [['k'=>'a','l'=>'(١,١)','p'=>'أ'],['k'=>'b','l'=>'(١,٢)','p'=>'ب'],['k'=>'c','l'=>'(٢,١)','p'=>'ج'],['k'=>'d','l'=>'(٢,٢)','p'=>'د']], 'template' => '\\begin{vmatrix} %a% & %b% \\\\ %c% & %d% \\end{vmatrix}', 'sort_order' => 62],
            ['key' => 'vector',     'name' => 'متجه',        'category' => 'المصفوفات والمتجهات', 'icon' => '\\(\\vec{أ}\\)', 'inputs' => [['k'=>'v','l'=>'الرمز','p'=>'أ']], 'template' => '\\vec{%v%}', 'sort_order' => 63],
            ['key' => 'vectorCol',  'name' => 'متجه عمودي',  'category' => 'المصفوفات والمتجهات', 'icon' => '\\(\\begin{pmatrix}س\\\\ص\\\\ع\\end{pmatrix}\\)', 'inputs' => [['k'=>'a','l'=>'العنصر ١','p'=>'س'],['k'=>'b','l'=>'العنصر ٢','p'=>'ص'],['k'=>'c','l'=>'العنصر ٣','p'=>'ع']], 'template' => '\\begin{pmatrix} %a% \\\\ %b% \\\\ %c% \\end{pmatrix}', 'sort_order' => 64],

            // === الهندسة ===
            ['key' => 'angle',         'name' => 'زاوية',    'category' => 'الهندسة', 'icon' => '\\(\\angle أبج\\)', 'inputs' => [['k'=>'v','l'=>'الاسم','p'=>'أبج']], 'template' => '\\angle %v%', 'sort_order' => 70],
            ['key' => 'degrees',       'name' => 'درجة',     'category' => 'الهندسة', 'icon' => '\\(٩٠^{\\circ}\\)', 'inputs' => [['k'=>'v','l'=>'القيمة','p'=>'٩٠']], 'template' => '%v%^{\\circ}', 'sort_order' => 71],
            ['key' => 'parallel',      'name' => 'توازي',    'category' => 'الهندسة', 'icon' => '\\(أب \\parallel جد\\)', 'inputs' => [['k'=>'a','l'=>'الأول','p'=>'أب'],['k'=>'b','l'=>'الثاني','p'=>'جد']], 'template' => '%a% \\parallel %b%', 'sort_order' => 72],
            ['key' => 'perpendicular', 'name' => 'تعامد',    'category' => 'الهندسة', 'icon' => '\\(أب \\perp جد\\)', 'inputs' => [['k'=>'a','l'=>'الأول','p'=>'أب'],['k'=>'b','l'=>'الثاني','p'=>'جد']], 'template' => '%a% \\perp %b%', 'sort_order' => 73],
            ['key' => 'triangle',      'name' => 'مثلث',     'category' => 'الهندسة', 'icon' => '\\(\\triangle أبج\\)', 'inputs' => [['k'=>'v','l'=>'الرؤوس','p'=>'أبج']], 'template' => '\\triangle %v%', 'sort_order' => 74],
            ['key' => 'circle',        'name' => 'دائرة',    'category' => 'الهندسة', 'icon' => '\\(\\odot م\\)', 'inputs' => [['k'=>'v','l'=>'المركز','p'=>'م']], 'template' => '\\odot %v%', 'sort_order' => 75],
            ['key' => 'line',          'name' => 'مستقيم',   'category' => 'الهندسة', 'icon' => '\\(\\overleftrightarrow{أب}\\)', 'inputs' => [['k'=>'v','l'=>'النقطتان','p'=>'أب']], 'template' => '\\overleftrightarrow{%v%}', 'sort_order' => 76],
            ['key' => 'ray',           'name' => 'شعاع',     'category' => 'الهندسة', 'icon' => '\\(\\overrightarrow{أب}\\)', 'inputs' => [['k'=>'v','l'=>'النقطتان','p'=>'أب']], 'template' => '\\overrightarrow{%v%}', 'sort_order' => 77],

            // === المجموعات ===
            ['key' => 'setIn',        'name' => 'ينتمي',          'category' => 'المجموعات', 'icon' => '\\(س \\in م\\)', 'inputs' => [['k'=>'a','l'=>'العنصر','p'=>'س'],['k'=>'b','l'=>'المجموعة','p'=>'م']], 'template' => '%a% \\in %b%', 'sort_order' => 80],
            ['key' => 'setNotIn',     'name' => 'لا ينتمي',       'category' => 'المجموعات', 'icon' => '\\(س \\notin م\\)', 'inputs' => [['k'=>'a','l'=>'العنصر','p'=>'س'],['k'=>'b','l'=>'المجموعة','p'=>'م']], 'template' => '%a% \\notin %b%', 'sort_order' => 81],
            ['key' => 'setUnion',     'name' => 'اتحاد',          'category' => 'المجموعات', 'icon' => '\\(أ \\cup ب\\)', 'inputs' => [['k'=>'a','l'=>'الأولى','p'=>'أ'],['k'=>'b','l'=>'الثانية','p'=>'ب']], 'template' => '%a% \\cup %b%', 'sort_order' => 82],
            ['key' => 'setIntersect', 'name' => 'تقاطع',          'category' => 'المجموعات', 'icon' => '\\(أ \\cap ب\\)', 'inputs' => [['k'=>'a','l'=>'الأولى','p'=>'أ'],['k'=>'b','l'=>'الثانية','p'=>'ب']], 'template' => '%a% \\cap %b%', 'sort_order' => 83],
            ['key' => 'subset',       'name' => 'مجموعة جزئية',   'category' => 'المجموعات', 'icon' => '\\(أ \\subseteq ب\\)', 'inputs' => [['k'=>'a','l'=>'الجزئية','p'=>'أ'],['k'=>'b','l'=>'الكلية','p'=>'ب']], 'template' => '%a% \\subseteq %b%', 'sort_order' => 84],
            ['key' => 'setEmpty',     'name' => 'مجموعة خالية',   'category' => 'المجموعات', 'icon' => '\\(\\emptyset\\)', 'inputs' => [], 'template' => '\\emptyset', 'sort_order' => 85],

            // === الأقواس والحدود ===
            ['key' => 'parentheses', 'name' => 'أقواس دائرية',  'category' => 'الأقواس والحدود', 'icon' => '\\(\\left(س\\right)\\)', 'inputs' => [['k'=>'v','l'=>'المحتوى','p'=>'س+١']], 'template' => '\\left(%v%\\right)', 'sort_order' => 90],
            ['key' => 'brackets',    'name' => 'أقواس مربعة',   'category' => 'الأقواس والحدود', 'icon' => '\\(\\left[س\\right]\\)', 'inputs' => [['k'=>'v','l'=>'المحتوى','p'=>'س+١']], 'template' => '\\left[%v%\\right]', 'sort_order' => 91],
            ['key' => 'braces',      'name' => 'أقواس معقوصة',  'category' => 'الأقواس والحدود', 'icon' => '\\(\\left\\{س\\right\\}\\)', 'inputs' => [['k'=>'v','l'=>'المحتوى','p'=>'١,٢,٣']], 'template' => '\\left\\{%v%\\right\\}', 'sort_order' => 92],
            ['key' => 'floor',       'name' => 'دالة الأرضية',  'category' => 'الأقواس والحدود', 'icon' => '\\(\\lfloor س \\rfloor\\)', 'inputs' => [['k'=>'v','l'=>'القيمة','p'=>'٣.٧']], 'template' => '\\lfloor %v% \\rfloor', 'sort_order' => 93],
            ['key' => 'ceil',        'name' => 'دالة السقف',    'category' => 'الأقواس والحدود', 'icon' => '\\(\\lceil س \\rceil\\)', 'inputs' => [['k'=>'v','l'=>'القيمة','p'=>'٣.٢']], 'template' => '\\lceil %v% \\rceil', 'sort_order' => 94],
            ['key' => 'binom',       'name' => 'توافيق',        'category' => 'الأقواس والحدود', 'icon' => '\\(\\binom{ن}{ك}\\)', 'inputs' => [['k'=>'n','l'=>'ن','p'=>'٥'],['k'=>'k','l'=>'ك','p'=>'٢']], 'template' => '\\binom{%n%}{%k%}', 'sort_order' => 95],

            // === الرموز الشائعة ===
            ['key' => 'pi',            'name' => 'ط (باي)',       'category' => 'الرموز الشائعة', 'icon' => '\\(\\pi\\)', 'inputs' => [['k'=>'c','l'=>'المعامل (اختياري)','p'=>'']], 'template' => '%c%\\pi', 'sort_order' => 100],
            ['key' => 'percent',       'name' => 'نسبة مئوية',   'category' => 'الرموز الشائعة', 'icon' => '\\(٢٥\\%\\)', 'inputs' => [['k'=>'v','l'=>'القيمة','p'=>'٢٥']], 'template' => '%v%\\%', 'sort_order' => 101],
            ['key' => 'abs',           'name' => 'قيمة مطلقة',   'category' => 'الرموز الشائعة', 'icon' => '\\(|س|\\)', 'inputs' => [['k'=>'v','l'=>'القيمة','p'=>'س']], 'template' => '|%v%|', 'sort_order' => 102],
            ['key' => 'infinity',      'name' => 'لا نهاية',     'category' => 'الرموز الشائعة', 'icon' => '\\(\\infty\\)', 'inputs' => [['k'=>'s','l'=>'الإشارة (+ - فارغ)','p'=>'']], 'template' => '%s%\\infty', 'sort_order' => 103],
            ['key' => 'therefore',     'name' => 'إذن',          'category' => 'الرموز الشائعة', 'icon' => '\\(\\therefore\\)', 'inputs' => [], 'template' => '\\therefore', 'sort_order' => 104],
            ['key' => 'because',       'name' => 'بما أن',       'category' => 'الرموز الشائعة', 'icon' => '\\(\\because\\)', 'inputs' => [], 'template' => '\\because', 'sort_order' => 105],
            ['key' => 'plusMinusSign', 'name' => 'رمز ±',         'category' => 'الرموز الشائعة', 'icon' => '\\(\\pm\\)', 'inputs' => [], 'template' => '\\pm', 'sort_order' => 106],
            ['key' => 'notEqual',      'name' => 'لا يساوي',     'category' => 'الرموز الشائعة', 'icon' => '\\(\\neq\\)', 'inputs' => [], 'template' => '\\neq', 'sort_order' => 107],
            ['key' => 'dots',          'name' => 'نقاط',         'category' => 'الرموز الشائعة', 'icon' => '\\(\\cdots\\)', 'inputs' => [['k'=>'t','l'=>'النوع (h أفقي / v عمودي / d قطري)','p'=>'h']], 'template' => '%t%', 'sort_order' => 108],

            // === النصوص والتنسيق ===
            ['key' => 'textAbove',  'name' => 'نص فوق سهم',  'category' => 'النصوص والتنسيق', 'icon' => '\\(\\xrightarrow{نص}\\)', 'inputs' => [['k'=>'t','l'=>'النص','p'=>'يؤول'],['k'=>'b','l'=>'نص أسفل (اختياري)','p'=>'']], 'template' => '\\xrightarrow[%b%]{%t%}', 'sort_order' => 110],
            ['key' => 'overline',   'name' => 'خط فوق',      'category' => 'النصوص والتنسيق', 'icon' => '\\(\\overline{أب}\\)', 'inputs' => [['k'=>'v','l'=>'المحتوى','p'=>'أب']], 'template' => '\\overline{%v%}', 'sort_order' => 111],
            ['key' => 'underline',  'name' => 'خط تحت',      'category' => 'النصوص والتنسيق', 'icon' => '\\(\\underline{أب}\\)', 'inputs' => [['k'=>'v','l'=>'المحتوى','p'=>'أب']], 'template' => '\\underline{%v%}', 'sort_order' => 112],
            ['key' => 'cancel',     'name' => 'شطب',         'category' => 'النصوص والتنسيق', 'icon' => '\\(\\cancel{س}\\)', 'inputs' => [['k'=>'v','l'=>'المحتوى','p'=>'س']], 'template' => '\\cancel{%v%}', 'sort_order' => 113],
            ['key' => 'boxed',      'name' => 'إطار',        'category' => 'النصوص والتنسيق', 'icon' => '\\(\\boxed{س=١}\\)', 'inputs' => [['k'=>'v','l'=>'المحتوى','p'=>'س=١']], 'template' => '\\boxed{%v%}', 'sort_order' => 114],
            ['key' => 'color',      'name' => 'لون',         'category' => 'النصوص والتنسيق', 'icon' => '\\(\\textcolor{red}{س}\\)', 'inputs' => [['k'=>'c','l'=>'اللون (red blue green)','p'=>'red'],['k'=>'v','l'=>'المحتوى','p'=>'س']], 'template' => '\\textcolor{%c%}{%v%}', 'sort_order' => 115],

            // === يدوي ===
            ['key' => 'custom', 'name' => 'كتابة يدوية', 'category' => 'يدوي', 'icon' => '\\(\\cdots\\)', 'inputs' => [['k'=>'code','l'=>'اكتب كود LaTeX (بدون $)','p'=>'\\frac{١}{٢} + \\sqrt{٣}']], 'template' => '%code%', 'sort_order' => 200],
        ];

        foreach ($formats as $format) {
            LatexFormat::updateOrCreate(
                ['key' => $format['key']],
                $format
            );
        }
    }
}
