<x-layouts.app :title="$page->meta_title ?? $page->title" :meta-description="$page->meta_description">

<header class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pt-8 pb-6 text-center">
    <h1 class="text-3xl sm:text-4xl font-semibold italic mb-3 text-brand-700"
        style="font-family: Georgia, serif;">
        {{ $page->title }}
    </h1>
    <div class="mt-5 mx-auto w-16 border-t-2 border-brand-200"></div>
</header>

<article class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 pb-16">
    <div class="page-content">
        {!! $page->content !!}
    </div>
</article>

</x-layouts.app>
