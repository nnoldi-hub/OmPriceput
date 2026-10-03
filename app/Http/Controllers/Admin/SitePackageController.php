<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SitePackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SitePackageController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/SitePackages/Index', [
            'packages' => SitePackage::orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/SitePackages/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        SitePackage::create($this->validateData($request));

        return redirect()->route('admin.site-packages.index')->with('success', 'Pachet creat.');
    }

    public function edit(SitePackage $sitePackage): Response
    {
        return Inertia::render('Admin/SitePackages/Edit', ['package' => $sitePackage]);
    }

    public function update(Request $request, SitePackage $sitePackage): RedirectResponse
    {
        $sitePackage->update($this->validateData($request, $sitePackage));

        return redirect()->route('admin.site-packages.index')->with('success', 'Pachet actualizat.');
    }

    public function destroy(SitePackage $sitePackage): RedirectResponse
    {
        $sitePackage->delete();

        return redirect()->route('admin.site-packages.index')->with('success', 'Pachet sters.');
    }

    private function validateData(Request $request, ?SitePackage $package = null): array
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/', Rule::unique('site_packages', 'key')->ignore($package)],
            'name' => ['required', 'string', 'max:255'],
            'price_from' => ['required', 'numeric', 'min:0'],
            'vizite_an' => ['required', 'integer', 'min:0'],
            'timp_raspuns' => ['nullable', 'string', 'max:100'],
            'prioritate' => ['nullable', 'string', 'max:100'],
            'features' => ['required', 'string'],
            'highlight' => ['boolean'],
            'active' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $data['features'] = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $data['features']))));
        $data['highlight'] = $request->boolean('highlight');
        $data['active'] = $request->boolean('active');

        return $data;
    }
}
