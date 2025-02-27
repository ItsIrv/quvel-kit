export default {
  required: '{attribute} is required.',
  email: '{attribute} must be a valid email address.',
  minLength: '{attribute} must be at least {min} characters.',
  maxLength: '{attribute} cannot exceed {max} characters.',
  invalid_type: '{attribute} must be a {expectedType}, but got {receivedType}.',
  invalid_enum_value: '{attribute} must be one of: {options}. Received: {received}.',
  unrecognized_keys: '{attribute} contains unknown fields: {keys}.',
  invalid_union: '{attribute} does not match any valid types.',
  invalid_date: '{attribute} must be a valid date.',
  not_multiple_of: '{attribute} must be a multiple of {multipleOf}.',
  not_finite: '{attribute} must be a finite number.',
  default: 'Invalid value for {attribute}.',
};
