<h5>{{ __('customers.address.street_house_title') }}</h5>
<div class="row">
    <div class="col-md-6">
        <label>{{ __('customers.address.house_number') }}</label>
        <input type="text" name="house_number" value="{{ old('house_number', $address->house_number ?? '') }}" class="form-control">
    </div>
    <div class="col-md-6">
        <label>{{ __('customers.address.temporary_number') }}</label>
        <input type="text" name="temporary_number" value="{{ old('temporary_number', $address->temporary_number ?? '') }}" class="form-control">
    </div>
</div> 

<hr>

<h5>{{ __('customers.address.apartment_title') }}</h5>
<div class="row">
    <div class="col-md-6">
        <label>{{ __('customers.address.project_name') }}</label>
        <input type="text" name="project_name" value="{{ old('project_name', $address->project_name ?? '') }}" class="form-control">
    </div>
    <div class="col-md-6">
        <label>{{ __('customers.address.block') }}</label>
        <input type="text" name="block" value="{{ old('block', $address->block ?? '') }}" class="form-control">
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <label>{{ __('customers.address.floor') }}</label>
        <input type="text" name="floor" value="{{ old('floor', $address->floor ?? '') }}" class="form-control">
    </div>
    <div class="col-md-6">
        <label>{{ __('customers.address.unit_number') }}</label>
        <input type="text" name="unit_number" value="{{ old('unit_number', $address->unit_number ?? '') }}" class="form-control">
    </div>
</div>

<hr>

<h5>{{ __('customers.address.common_title') }}</h5>
<div class="row">
    <div class="col-md-6">
        <label>{{ __('customers.address.street') }}</label>
        <input type="text" name="street" value="{{ old('street', $address->street ?? '') }}" class="form-control">
    </div> 
    <div class="col-md-6">
        <label>{{ __('customers.address.ward') }}</label>
        <input type="text" name="ward" value="{{ old('ward', $address->ward ?? '') }}" class="form-control">
    </div>
    <div class="col-md-6">
        <label>{{ __('customers.address.district') }}</label>
        <input type="text" name="district" value="{{ old('district', $address->district ?? '') }}" class="form-control">
    </div> 
    <div class="col-md-6">
        <label>{{ __('customers.address.city_full') }}</label>
        <input type="text" name="city" value="{{ old('city', $address->city ?? '') }}" class="form-control">
    </div>
    <div class="col-md-6">
        <label>{{ __('customers.address.is_default') }}</label><br>
        <input type="checkbox" name="is_default" value="1" {{ old('is_default', $address->is_default ?? false) ? 'checked' : '' }}>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-12">
        <label>{{ __('customers.form.note') }}</label>
        <textarea name="note" class="form-control">{{ old('note', $address->note ?? '') }}</textarea>
    </div>
</div>
