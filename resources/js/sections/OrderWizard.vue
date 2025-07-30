<template>
  <div class="row">
    <div class="col-12">
      <div class="bg-light p-sm-5 p-3 mb-5 booking-detail-service-box rounded-4">
        <div class="row align-items-center">
          <div class="col-lg-3 col-md-4">
            <div class="img flex-shrink-0">
              <img
                :src="product.product_image"
                class="object-cover rounded-3 w-100 img-fluid book-service-img"
                alt="product"
              />
            </div>
          </div>
          <div class="col-lg-9 col-md-8 mt-md-0 mt-3">
            <div class="content flex-grow-1">
              <div class="d-sm-flex align-items-center gap-3 justify-content-between">
                <h4 class="mb-0">{{ product.name }}</h4>
                <div class="flex-shrink-0 d-inline-flex align-items-center gap-2 mt-sm-0 mt-2">
                  <span class="text-primary fw-500 d-inline-block position-relative h5">
                    ر.س{{ product.price }}
                  </span>
                </div>
              </div>
              <div class="d-sm-flex gap-2 mt-3">
                <h6 class="m-0 lh-1">{{ $t('messages.category') }}:</h6>
                <ul
                  class="list-inline mt-sm-0 mt-2 mb-0 p-0 d-flex align-items-center flex-wrap category-list lh-1"
                >
                  <li>{{ product.category_name }}</li>
                </ul>
              </div>
              <div class="d-flex align-items-center flex-wrap gap-2 mt-4">
                <div class="d-flex align-items-center gap-1 flex-shrink-0">
                  <span class="text-warning">
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      width="12"
                      height="12"
                      viewBox="0 0 12 12"
                      fill="none"
                      class="service-rating"
                    >
                      <path
                        d="M6.58578 0.85525L7.92167 3.44562C8.02009 3.63329 8.20793 3.76362 8.42458 3.79259L11.4252 4.21427C11.6005 4.23802 11.7595 4.32723 11.8669 4.46335C11.9731 4.59773 12.0187 4.76803 11.9929 4.93543C11.9719 5.07445 11.9041 5.20304 11.8003 5.30151L9.62603 7.33523C9.467 7.47714 9.39498 7.68741 9.43339 7.89304L9.96871 10.7522C10.0257 11.0974 9.78867 11.4229 9.43339 11.4884C9.28696 11.511 9.13693 11.4872 9.0049 11.4224L6.32833 10.0768C6.12968 9.98005 5.89503 9.98005 5.69639 10.0768L3.01982 11.4224C2.69094 11.5909 2.28346 11.4762 2.10042 11.1634C2.0326 11.0389 2.0086 10.897 2.0308 10.7585L2.56612 7.89883C2.60453 7.69378 2.53191 7.48236 2.37348 7.34044L0.19921 5.30788C-0.0594455 5.06692 -0.0672472 4.67014 0.181806 4.42048C0.187207 4.41527 0.193209 4.40948 0.19921 4.40369C0.302432 4.30232 0.438061 4.23802 0.584493 4.22123L3.58514 3.79896C3.80118 3.76942 3.98902 3.64025 4.08805 3.45141L5.37592 0.85525C5.49055 0.632821 5.7282 0.494383 5.98625 0.500175H6.06667C6.29052 0.526241 6.48556 0.660046 6.58578 0.85525Z"
                        fill="currentColor"
                      ></path>
                    </svg>
                  </span>
                  <h6 class="font-size-14">
                    {{ product.total_rating
                    }}<span class="text-body">
                      ({{ product.total_reviews }} {{ $t('messages.reviews') }})</span
                    >
                  </h6>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12">
      <form @submit.prevent="formSubmit" class="form-disabled">
        <div class="row">
          <div class="col-lg-8">
            <div class="mt-5 card bg-light rounded-3">
              <div class="card-body booking-service-form">
                <div class="row">
                  <!-- Quantity Selection -->
                  <div class="col-md-6">
                    <div class="custom-form-field">
                      <label class="form-label">{{ $t('messages.quantity') }}</label>
                      <div class="d-flex align-items-center gap-3">
                        <button
                          type="button"
                          class="btn btn-outline-primary"
                          @click="decreaseQuantity"
                          :disabled="quantity <= 1"
                        >
                          <i class="fas fa-minus"></i>
                        </button>
                        <span class="fw-bold fs-5 px-3">{{ quantity }}</span>
                        <button
                          type="button"
                          class="btn btn-outline-primary"
                          @click="increaseQuantity"
                          :disabled="quantity >= product.stock_quantity"
                        >
                          <i class="fas fa-plus"></i>
                        </button>
                      </div>
                      <small class="text-muted mt-1 d-block"
                        >Available: {{ product.stock_quantity }} units</small
                      >
                    </div>
                  </div>

                  <!-- Delivery Address -->
                  <div class="col-12">
                    <div class="custom-form-field">
                      <label class="form-label">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        Delivery Address
                      </label>
                      <textarea
                        v-model="address"
                        class="form-control"
                        rows="3"
                        placeholder="Enter your complete delivery address"
                        required
                      ></textarea>
                      <span v-if="errorMessages['address']">
                        <ul class="text-danger">
                          <li v-for="err in errorMessages['address']" :key="err">{{ err }}</li>
                        </ul>
                      </span>
                    </div>
                  </div>

                  <!-- Contact Number -->
                  <div class="col-md-6">
                    <div class="custom-form-field">
                      <label class="form-label">
                        <i class="fas fa-phone me-2"></i>
                        Contact Number
                      </label>
                      <input
                        type="tel"
                        v-model="contact_number"
                        class="form-control"
                        placeholder="Enter your phone number"
                        required
                      />
                      <span v-if="errorMessages['contact_number']">
                        <ul class="text-danger">
                          <li v-for="err in errorMessages['contact_number']" :key="err">
                            {{ err }}
                          </li>
                        </ul>
                      </span>
                    </div>
                  </div>

                  <!-- Delivery Notes -->
                  <div class="col-md-6">
                    <div class="custom-form-field">
                      <label class="form-label">
                        <i class="fas fa-sticky-note me-2"></i>
                        Delivery Notes <span class="text-muted">(Optional)</span>
                      </label>
                      <textarea
                        v-model="delivery_notes"
                        class="form-control"
                        rows="2"
                        placeholder="Any special delivery instructions"
                      ></textarea>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Order Summary -->
          <div class="col-lg-4">
            <div class="mt-5 card bg-light rounded-3">
              <div class="card-body">
                <h5 class="mb-4">{{ $t('messages.order_summary') }}</h5>

                <div class="d-flex justify-content-between mb-2">
                  <span>{{ $t('messages.price') }}</span>
                  <span>ر.س{{ product.price }}</span>
                </div>

                <div class="d-flex justify-content-between mb-2">
                  <span>{{ $t('messages.quantity') }}</span>
                  <span>{{ quantity }}</span>
                </div>

                <div class="d-flex justify-content-between mb-2">
                  <span>{{ $t('messages.subtotal') }}</span>
                  <span>ر.س{{ subtotal }}</span>
                </div>

                <div class="d-flex justify-content-between mb-2">
                  <span>{{ $t('messages.delivery_fee') }}</span>
                  <span>ر.س{{ deliveryFee }}</span>
                </div>

                <hr />

                <div class="d-flex justify-content-between fw-bold">
                  <span>{{ $t('messages.total_amount') }}</span>
                  <span>ر.س{{ totalAmount }}</span>
                </div>

                <div class="mt-4">
                  <button type="submit" class="btn btn-primary w-100">
                    <span
                      v-if="IsLoading"
                      class="spinner-border spinner-border-sm"
                      role="status"
                      aria-hidden="true"
                    ></span>
                    <span v-else>{{ $t('messages.order_now') }}</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useForm } from 'vee-validate'
import Swal from 'sweetalert2'

const props = defineProps({
  product: Object,
  coupons: Array,
  taxes: Array,
  user_id: Number,
  googlemapkey: String,
  wallet_amount: Number
})

// Form data
const quantity = ref(1)
const address = ref('')
const contact_number = ref('')
const delivery_notes = ref('')
const deliveryFee = ref(5.0) // Default delivery fee

// Loading state
const IsLoading = ref(false)
const errorMessages = ref({})

// Computed values
const subtotal = computed(() => {
  return props.product.price * quantity.value
})

const totalAmount = computed(() => {
  return subtotal.value + deliveryFee.value
})

// Methods
const increaseQuantity = () => {
  if (quantity.value < props.product.stock_quantity) {
    quantity.value++
  }
}

const decreaseQuantity = () => {
  if (quantity.value > 1) {
    quantity.value--
  }
}

// Form submission
const { handleSubmit } = useForm()

const formSubmit = handleSubmit(async (values) => {
  IsLoading.value = true

  const title = 'Confirm Order'
  const subtitle = 'Do you want to confirm this order?'

  const result = await Swal.fire({
    title: title,
    text: subtitle,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#5F60B9',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Yes, Order Now!'
  })

  if (!result.isConfirmed) {
    IsLoading.value = false
    return
  }

  try {
    const csrfToken = document.head.querySelector('[name~=csrf-token][content]').content

    const orderData = {
      product_id: props.product.id,
      quantity: quantity.value,
      address: address.value,
      contact_number: contact_number.value,
      delivery_notes: delivery_notes.value,
      subtotal: subtotal.value,
      delivery_fee: deliveryFee.value,
      total_amount: totalAmount.value
    }

    const response = await fetch('/api/orders', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrfToken
      },
      body: JSON.stringify(orderData)
    })

    if (response.ok) {
      const responseData = await response.json()

      IsLoading.value = false

      await Swal.fire({
        title: 'Order Placed!',
        text: responseData.message || 'Your order has been placed successfully.',
        icon: 'success',
        iconColor: '#5F60B9'
      })

      // Redirect to order list
      const baseUrl = document.querySelector('meta[name="baseUrl"]').getAttribute('content')
      window.location.href = baseUrl + '/order-list'
    } else {
      const errorData = await response.json()
      throw new Error(errorData.message || 'Failed to place order')
    }
  } catch (error) {
    IsLoading.value = false

    await Swal.fire({
      title: 'Error',
      text: error.message || 'Something went wrong. Please try again.',
      icon: 'error',
      iconColor: '#dc3545'
    })
  }
})

onMounted(() => {
  // Initialize any required data
})
</script>

<style scoped>
.booking-detail-service-box {
  border: 1px solid #e9ecef;
}

.book-service-img {
  height: 120px;
  object-fit: cover;
}

.custom-form-field {
  margin-bottom: 1.5rem;
}

.form-label {
  font-weight: 600;
  margin-bottom: 0.5rem;
}
</style>
