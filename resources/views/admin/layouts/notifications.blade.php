{{-- Cloche : activation des notifications push et installation de l'app --}}
<div x-data="adminPush" class="relative">
    <button type="button" @click="open = !open" aria-label="Notifications"
        class="relative flex items-center justify-center w-10 h-10 rounded-lg text-gray-500 hover:bg-gray-100">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 00-4-5.7V5a2 2 0 10-4 0v.3A6 6 0 006 11v3.2a2 2 0 01-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        <span x-show="state === 'on'" x-cloak class="absolute top-2 right-2 w-2 h-2 rounded-full bg-green-500 ring-2 ring-white"></span>
        <span x-show="['off', 'ios-install'].includes(state)" x-cloak class="absolute top-2 right-2 w-2 h-2 rounded-full bg-amber-500 ring-2 ring-white"></span>
    </button>

    <div x-show="open" @click.away="open = false" x-transition x-cloak
        class="fixed inset-x-3 top-16 sm:absolute sm:inset-x-auto sm:top-auto sm:right-0 sm:mt-2 sm:w-80 rounded-xl border border-gray-200 bg-white p-4 shadow-lg">
        <h3 class="text-sm font-semibold text-gray-800">Notifications de commande</h3>

        <p class="mt-1 text-sm text-gray-500" x-show="state === 'loading'">Vérification…</p>

        <div x-show="state === 'on'" class="mt-1 space-y-3">
            <p class="text-sm text-gray-500">Cet appareil est prévenu à chaque nouvelle commande payée.</p>
            <button type="button" @click="test()" :disabled="busy"
                class="w-full py-2.5 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700 transition disabled:opacity-50">
                Envoyer une notification de test
            </button>
            <button type="button" @click="disable()" :disabled="busy"
                class="w-full py-2 text-sm text-gray-500 hover:text-gray-700 disabled:opacity-50">
                Désactiver sur cet appareil
            </button>
        </div>

        <div x-show="state === 'off'" class="mt-1 space-y-3">
            <p class="text-sm text-gray-500">Recevez une notification sur cet appareil dès qu'une commande est payée.</p>
            <button type="button" @click="enable()" :disabled="busy"
                class="w-full py-2.5 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700 transition disabled:opacity-50">
                Activer les notifications
            </button>
        </div>

        <div x-show="state === 'ios-install'" class="mt-1 space-y-2 text-sm text-gray-500">
            <p>Sur iPhone, les notifications passent par l'application installée :</p>
            <ol class="list-decimal pl-5 space-y-1">
                <li>dans Safari, touchez <span class="font-medium text-gray-700">Partager</span> ;</li>
                <li>choisissez <span class="font-medium text-gray-700">Sur l'écran d'accueil</span> ;</li>
                <li>ouvrez l'app « Atelier » et revenez sur cette cloche.</li>
            </ol>
        </div>

        <p x-show="state === 'denied'" class="mt-1 text-sm text-gray-500">
            Les notifications sont bloquées pour ce site. Autorisez-les dans les réglages du navigateur ou du téléphone, puis rechargez la page.
        </p>

        <p x-show="state === 'unconfigured'" class="mt-1 text-sm text-gray-500">
            Notifications non configurées sur le serveur : lancez <code class="text-xs bg-gray-100 rounded px-1 py-0.5">php artisan webpush:vapid</code> et ajoutez les clés au fichier .env.
        </p>

        <p x-show="state === 'unsupported'" class="mt-1 text-sm text-gray-500">
            Ce navigateur ne prend pas en charge les notifications push.
        </p>

        <template x-if="message">
            <p class="mt-3 rounded-lg border p-2.5 text-sm"
               :class="messageType === 'success' ? 'border-green-200 bg-green-50 text-green-800' : 'border-red-200 bg-red-50 text-red-800'"
               x-text="message"></p>
        </template>

        <button type="button" x-show="canInstall" @click="install()"
            class="mt-3 w-full py-2 border border-brand-200 rounded-lg text-sm font-medium text-brand-700 hover:bg-brand-50 transition">
            Installer l'application
        </button>
    </div>
</div>
