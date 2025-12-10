# Alpine.js Date Validation Component

## Overview

The `vesselCallForm` Alpine.js component provides real-time and submit-time validation for vessel call date fields, ensuring temporal consistency according to business rules.

## Validation Rules

The component enforces the following date validation rules:

1. **ETB >= ETA**: Estimated Time of Berthing must be greater than or equal to Estimated Time of Arrival
2. **ATB >= ATA**: Actual Time of Berthing must be greater than or equal to Actual Time of Arrival
3. **ATD >= ATB**: Actual Time of Departure must be greater than or equal to Actual Time of Berthing

## Features

### Real-time Validation
- Validates dates as the user types or changes values
- Shows inline error messages immediately below invalid fields
- Applies red border styling to fields with validation errors

### Submit-time Validation
- Prevents form submission if any validation rules are violated
- Shows a consolidated error message at the top of the form
- Maintains focus on the form for user correction

### Visual Feedback
- Invalid fields receive red border styling
- Error messages appear in red text below the field
- Help text provides guidance on expected values

## Usage

### In Blade Templates

```blade
<form x-data="vesselCallForm()" @submit="validateDates($event)">
    <!-- For create forms -->
    <input type="datetime-local" 
           name="eta" 
           x-model="eta"
           :class="getFieldClass('eta', 'base-classes-here')">
    
    <p x-show="hasError('eta')" x-text="getError('eta')" class="text-red-600"></p>
</form>
```

### For Edit Forms with Initial Data

```blade
<form x-data="vesselCallForm({
    eta: '{{ $vesselCall->eta->format('Y-m-d\TH:i') }}',
    etb: '{{ $vesselCall->etb->format('Y-m-d\TH:i') }}',
    ata: '{{ $vesselCall->ata->format('Y-m-d\TH:i') }}',
    atb: '{{ $vesselCall->atb->format('Y-m-d\TH:i') }}',
    atd: '{{ $vesselCall->atd->format('Y-m-d\TH:i') }}'
})" @submit="validateDates($event)">
    <!-- Form fields -->
</form>
```

## Component API

### Properties

- `eta`: Estimated Time of Arrival
- `etb`: Estimated Time of Berthing
- `ata`: Actual Time of Arrival
- `atb`: Actual Time of Berthing
- `atd`: Actual Time of Departure
- `validationError`: General validation error message (shown on submit)
- `fieldErrors`: Object containing field-specific error messages

### Methods

#### `validateField(field)`
Validates a specific field in real-time when its value changes.

#### `validateDates(event)`
Validates all date fields on form submission. Prevents submission if validation fails.

#### `hasError(field)`
Returns `true` if the specified field has a validation error.

#### `getError(field)`
Returns the error message for the specified field, or empty string if no error.

#### `getFieldClass(field, baseClass)`
Returns the CSS classes for a field, including error styling if applicable.

## Implementation Details

### Location
- Component definition: `resources/js/app.js`
- Used in: 
  - `resources/views/portuario/vessel-calls/create.blade.php`
  - `resources/views/portuario/vessel-calls/edit.blade.php`

### Build Process
After modifying the component, rebuild assets:
```bash
npm run build
```

For development with hot reload:
```bash
npm run dev
```

## Testing

### Manual Testing Steps

1. Navigate to vessel call create/edit form
2. Enter an ETA value
3. Enter an ETB value that is earlier than ETA
4. Observe red border and error message appear immediately
5. Correct the ETB value to be >= ETA
6. Observe error message disappear
7. Attempt to submit form with invalid dates
8. Observe form submission is prevented and error message appears

### Expected Behavior

- ✅ Real-time validation triggers on field change
- ✅ Error messages appear below invalid fields
- ✅ Invalid fields receive red border styling
- ✅ Form submission is prevented when validation fails
- ✅ Error messages clear when values are corrected
- ✅ Multiple validation errors can be shown simultaneously

## Requirements Validation

This component satisfies the following requirements from the spec:

- **US-1.1 Acceptance Criteria**: "Validación: ETB >= ETA, ATB >= ATA, ATD >= ATB"
- **Task**: "Implementar componente Alpine.js para validación de fechas (ETB >= ETA, etc.)"

## Browser Compatibility

The component uses standard JavaScript Date objects and Alpine.js features that are compatible with:
- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)

## Future Enhancements

Potential improvements for future iterations:
- Add timezone awareness for international operations
- Implement server-side validation mirroring
- Add visual timeline representation
- Include business hours validation
- Add warnings for unusual time gaps
