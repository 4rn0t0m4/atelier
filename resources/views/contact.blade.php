<x-layouts.app title="Contact" meta-description="Contactez l'Atelier d'Aubin pour toute question sur nos créations en bois personnalisées.">

<header class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pt-8 pb-6 text-center">
    <h1 class="text-3xl sm:text-4xl font-semibold italic mb-3 text-brand-700"
        style="font-family: Georgia, serif;">
        Contact
    </h1>
    <p class="text-brand-500 text-sm">Une question, une demande de personnalisation ? Écrivez-nous !</p>
    <div class="mt-5 mx-auto w-16 border-t-2 border-brand-200"></div>
</header>

<div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 pb-16">

    @if(session('status'))
        <div class="bg-green-50 border border-green-200 text-green-700 text-sm rounded px-4 py-3 mb-6">
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded px-4 py-3 mb-6">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ route('contact.send') }}" method="POST" class="space-y-5" data-turbo="false">
        @csrf

        <div>
            <label for="name" class="block text-sm text-gray-700 mb-1">Nom <span class="text-red-400">*</span></label>
            <input type="text" name="name" id="name" value="{{ old('name') }}"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500"
                   required>
        </div>

        <div>
            <label for="email" class="block text-sm text-gray-700 mb-1">E-mail <span class="text-red-400">*</span></label>
            <input type="email" name="email" id="email" value="{{ old('email') }}"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500"
                   required>
        </div>

        <div>
            <label for="message" class="block text-sm text-gray-700 mb-1">Message <span class="text-red-400">*</span></label>
            <textarea name="message" id="message" rows="6"
                      class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500"
                      required>{{ old('message') }}</textarea>
        </div>

        <button type="submit"
                class="w-full bg-brand-700 text-white py-2.5 rounded font-medium hover:bg-brand-800 transition text-sm">
            Envoyer le message
        </button>
    </form>
</div>

</x-layouts.app>
