<template>
  <div class="add-to-cart-wrapper">
    <!-- Quantity Selector -->
    <div v-if="showQuantitySelector" class="quantity-selector mb-3">
      <label class="form-label">Quantity:</label>
      <div class="quantity-controls">
        <button 
          @click="decreaseQuantity"
          :disabled="quantity <= 1 || loading"
          class="btn btn-outline-secondary"
          type="button"
        >
          <i class="fas fa-minus"></i>
        </button>
        <input 
          v-model.number="quantity"
          :disabled="loading"
          type="number" 
          min="1" 
          :max="maxQuantity"
          class="form-control quantity-input"
        >
        <button 
          @click="increaseQuantity"
          :disabled="quantity >= maxQuantity || loading"
          class="btn btn-outline-secondary"
          type="button"
        >
          <i class="fas fa-plus"></i>
        </button>
      </div>
    </div>

    <!-- Variant Selector -->
    <div v-if="variants.length > 0" class="variant-selector mb-3">
      <label class="form-label">Options:</label>
      <select 
        v-model="selectedVariant"
        :disabled="loading"
        class="form-select"
      >
        <option value="">Select an option</option>
        <option 
          v-for="variant in variants" 
          :key="variant.id"
          :value="variant.id"
          :disabled="!variant.is_active || variant.stock_quantity < quantity"
        >
          {{ variant.attribute_display }} 
          <span v-if="variant.price !== product.price">
            - ${{ formatPrice(variant.price) }}
          </span>
          <span v-if="variant.stock_quantity < 10">
            ({{ variant.stock_quantity }} left)
          </span>
        </option>
      </select>
    </div>

    <!-- Add to Cart Button -->
    <button 
      @click="addToCart"
      :disabled="!canAddToCart"
      :class="buttonClass"
      class="add-to-cart-btn"
    >
      <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
      <i v-else-if="!inCart" class="fas fa-shopping-cart me-2"></i>
      <i v-else class="fas fa-check me-2"></i>
      
      <span v-if="loading">Adding...</span>
      <span v-else-if="inCart">Added to Cart</span>
      <span v-else-if="outOfStock">Out of Stock</span>
      <span v-else>{{ buttonText }}</span>
    </button>

    <!-- Quick Actions -->
    <div v-if="showQuickActions && inCart" class="quick-actions mt-2">
      <a href="/cart" class="btn btn-outline-primary btn-sm me-2">
        <i class="fas fa-eye me-1"></i>
        View Cart
      </a>
      <a href="/checkout" class="btn btn-primary btn-sm">
        <i class="fas fa-credit-card me-1"></i>
        Checkout
      </a>
    </div>

    <!-- Success Message -->
    <div v-if="showSuccess" class="alert alert-success mt-3 fade-in">
      <i class="fas fa-check-circle me-2"></i>
      Item added to cart successfully!
    </div>

    <!-- Error Message -->
    <div v-if="error" class="alert alert-danger mt-3">
      <i class="fas fa-exclamation-triangle me-2"></i>
      {{ error }}
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import axios from 'axios'

// Props
const props = defineProps({
  product: {
    type: Object,
    required: true
  },
  variants: {
    type: Array,
    default: () => []
  },
  storeId: {
    type: [Number, String],
    default: null
  },
  buttonText: {
    type: String,
    default: 'Add to Cart'
  },
  buttonSize: {
    type: String,
    default: 'medium', // small, medium, large
    validator: (value) => ['small', 'medium', 'large'].includes(value)
  },
  buttonStyle: {
    type: String,
    default: 'primary', // primary, secondary, success, etc.
  },
  showQuantitySelector: {
    type: Boolean,
    default: true
  },
  showQuickActions: {
    type: Boolean,
    default: true
  },
  maxQuantity: {
    type: Number,
    default: 100
  }
})

// Emits
const emit = defineEmits(['added-to-cart', 'error'])

// Reactive data
const quantity = ref(1)
const selectedVariant = ref('')
const loading = ref(false)
const inCart = ref(false)
const showSuccess = ref(false)
const error = ref('')

// Computed
const canAddToCart = computed(() => {
  return !loading.value && 
         !outOfStock.value && 
         quantity.value > 0 && 
         quantity.value <= maxQuantity.value &&
         (props.variants.length === 0 || selectedVariant.value)
})

const outOfStock = computed(() => {
  if (selectedVariant.value) {
    const variant = props.variants.find(v => v.id == selectedVariant.value)
    return !variant || variant.stock_quantity < quantity.value
  }
  
  return props.product.stock_quantity < quantity.value
})

const buttonClass = computed(() => {
  const classes = ['btn']
  
  // Size
  if (props.buttonSize === 'small') classes.push('btn-sm')
  else if (props.buttonSize === 'large') classes.push('btn-lg')
  
  // Style
  if (loading.value) {
    classes.push('btn-secondary')
  } else if (inCart.value) {
    classes.push('btn-success')
  } else if (outOfStock.value) {
    classes.push('btn-outline-secondary')
  } else {
    classes.push(`btn-${props.buttonStyle}`)
  }
  
  return classes.join(' ')
})

const currentPrice = computed(() => {
  if (selectedVariant.value) {
    const variant = props.variants.find(v => v.id == selectedVariant.value)
    return variant ? variant.price : props.product.price
  }
  return props.product.price
})

// Methods
const addToCart = async () => {
  if (!canAddToCart.value) return
  
  loading.value = true
  error.value = ''
  showSuccess.value = false
  
  try {
    const response = await axios.post('/cart/add', {
      product_id: props.product.id,
      variant_id: selectedVariant.value || null,
      store_id: props.storeId || null,
      quantity: quantity.value
    })
    
    if (response.data.status) {
      inCart.value = true
      showSuccess.value = true
      
      // Hide success message after 3 seconds
      setTimeout(() => {
        showSuccess.value = false
      }, 3000)
      
      // Emit success event
      emit('added-to-cart', {
        product: props.product,
        variant: selectedVariant.value,
        quantity: quantity.value,
        cartCount: response.data.data.cart_count
      })
      
      // Dispatch global cart update event
      window.dispatchEvent(new CustomEvent('cart-updated', {
        detail: {
          count: response.data.data.cart_count,
          action: 'add',
          product: props.product
        }
      }))
      
    } else {
      error.value = response.data.message || 'Failed to add item to cart'
      emit('error', error.value)
    }
  } catch (err) {
    error.value = 'Failed to add item to cart'
    emit('error', error.value)
    console.error('Add to cart error:', err)
  } finally {
    loading.value = false
  }
}

const increaseQuantity = () => {
  if (quantity.value < maxQuantity.value) {
    quantity.value++
  }
}

const decreaseQuantity = () => {
  if (quantity.value > 1) {
    quantity.value--
  }
}

const checkIfInCart = async () => {
  try {
    const response = await axios.get('/api/cart/check-product', {
      params: {
        product_id: props.product.id,
        variant_id: selectedVariant.value || null,
        store_id: props.storeId || null
      }
    })
    
    if (response.data.status) {
      inCart.value = response.data.data.in_cart
    }
  } catch (err) {
    console.error('Check cart error:', err)
  }
}

const formatPrice = (price) => {
  return parseFloat(price || 0).toFixed(2)
}

// Watchers
watch([selectedVariant, () => props.storeId], () => {
  inCart.value = false
  checkIfInCart()
})

watch(quantity, (newQuantity) => {
  if (newQuantity < 1) quantity.value = 1
  if (newQuantity > maxQuantity.value) quantity.value = maxQuantity.value
})

// Lifecycle
onMounted(() => {
  checkIfInCart()
})
</script>

<style scoped>
.add-to-cart-wrapper {
  max-width: 400px;
}

.quantity-controls {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  max-width: 200px;
}

.quantity-input {
  width: 80px;
  text-align: center;
}

.quantity-controls .btn {
  width: 40px;
  height: 38px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0;
}

.add-to-cart-btn {
  width: 100%;
  font-weight: 600;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.add-to-cart-btn:disabled {
  cursor: not-allowed;
}

.add-to-cart-btn.btn-success {
  background-color: #28a745;
  border-color: #28a745;
}

.variant-selector .form-select {
  cursor: pointer;
}

.variant-selector .form-select option:disabled {
  color: #6c757d;
}

.quick-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.fade-in {
  animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.alert {
  font-size: 0.875rem;
  padding: 0.75rem;
}

/* Loading animation */
.add-to-cart-btn .spinner-border-sm {
  width: 1rem;
  height: 1rem;
}

/* Hover effects */
.add-to-cart-btn:not(:disabled):hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Mobile responsive */
@media (max-width: 768px) {
  .quantity-controls {
    max-width: 100%;
  }
  
  .quantity-input {
    width: 60px;
  }
  
  .quick-actions {
    flex-direction: column;
  }
  
  .quick-actions .btn {
    width: 100%;
  }
}

/* Success state animation */
.add-to-cart-btn.btn-success {
  animation: successPulse 0.6s ease-in-out;
}

@keyframes successPulse {
  0% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.05);
  }
  100% {
    transform: scale(1);
  }
}
</style>
