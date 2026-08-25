{{--
The developer API reference documents every version-one read contract and its privacy-safe shape.
--}}
<div class="mx-auto w-full max-w-6xl space-y-8 p-4 text-gray-900 dark:text-gray-100">
    @section('title')
        &vert; API v1
    @endsection

    <header class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-neutral-700 dark:bg-neutral-800">
        <p class="text-sm font-semibold tracking-wide text-emerald-700 uppercase dark:text-emerald-300">Developer reference</p>
        <h1 class="mt-2 text-3xl font-semibold">API v1</h1>
        <p class="mt-3 max-w-3xl text-sm leading-6 text-gray-600 dark:text-gray-300">
            Public, unauthenticated and read-only. Every response applies the same privacy and cross-team traversal rules as the public web interface.
        </p>
    </header>

    @foreach ($this->endpointGroups() as $group)
        <section
            aria-labelledby="api-group-{{ Str::slug($group['title']) }}"
            class="space-y-3"
        >
            <h2
                id="api-group-{{ Str::slug($group['title']) }}"
                class="text-2xl font-semibold"
            >
                {{ $group['title'] }}
            </h2>

            @foreach ($group['endpoints'] as $endpoint)
                <details class="group rounded-xl border border-gray-200 bg-white shadow-sm open:ring-2 open:ring-emerald-500 dark:border-neutral-700 dark:bg-neutral-800">
                    <summary class="flex cursor-pointer list-none items-center gap-3 rounded-xl p-5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600">
                        <span class="rounded-md bg-emerald-100 px-2 py-1 font-mono text-xs font-bold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">
                            {{ $endpoint['method'] }}
                        </span>
                        <code class="break-all text-sm font-semibold">{{ $endpoint['path'] }}</code>
                        <span
                            aria-hidden="true"
                            class="ml-auto text-xl transition group-open:rotate-45"
                        >+</span>
                    </summary>

                    <div class="space-y-5 border-t border-gray-200 p-5 dark:border-neutral-700">
                        <p class="text-sm leading-6 text-gray-600 dark:text-gray-300">{{ $endpoint['description'] }}</p>

                        <div>
                            <h3 class="font-semibold">Parameters</h3>
                            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-gray-600 dark:text-gray-300">
                                @foreach ($endpoint['parameters'] as $parameter)
                                    <li>{{ $parameter }}</li>
                                @endforeach
                            </ul>
                        </div>

                        <div>
                            <h3 class="font-semibold">Example request</h3>
                            <code class="mt-2 block overflow-x-auto rounded-lg bg-gray-950 p-4 text-sm text-gray-100">{{ $endpoint['example_request'] }}</code>
                        </div>

                        <div>
                            <h3 class="font-semibold">Example response</h3>
                            <pre class="mt-2 overflow-x-auto rounded-lg bg-gray-950 p-4 text-sm leading-6 text-gray-100"><code>{{ $endpoint['example_response'] }}</code></pre>
                        </div>
                    </div>
                </details>
            @endforeach
        </section>
    @endforeach
</div>
