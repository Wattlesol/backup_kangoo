<template>
  <div class="shopping-cart">
    <!-- Cart Header -->
    <div class="cart-header">
      <h4 class="cart-title">
        <i class="fas fa-shopping-cart me-2"></i>
        Shopping Cart
        <span v-if="cartData.total_items > 0" class="badge bg-primary ms-2">
          {{ cartData.total_items }}
        </span>
      </h4>
      <button 
        v-if="cartData.items.length > 0" 
        @click="clearCart" 
        class="btn btn-outline-danger btn-sm"
      >
        <i class="fas fa-trash me-1"></i>
        Clear Cart
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-4">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>

    <!-- Empty Cart -->
    <div v-else-if="cartData.items.length === 0" class="empty-cart text-center py-5">
      <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
      <h5 class="text-muted">Your cart is empty</h5>
      <p class="text-muted mb-4">Add some products to get started!</p>
      <a href="/store" class="btn btn-primary">
        <i class="fas fa-shopping-bag me-2"></i>
        Continue Shopping
      </a>
    </div>

    <!-- Cart Items -->
    <div v-else class="cart-content">
      <!-- Cart Items List -->
      <div class="cart-items">
        <div 
          v-for="item in cartData.items" 
          :key="item.item_key || item.id"
          class="cart-item"
        >
          <div class="item-image">
            <img 
              :src="item.product_image || '/images/default-product.png'" 
              :alt="item.product_name"
              class="img-fluid"
            >
          </div>
          
          <div class="item-details">
            <h6 class="item-name">{{ item.product_name }}</h6>
            <div class="item-meta">
              <span v-if="item.product_sku" class="sku">SKU: {{ item.product_sku }}</span>
              <span v-if="item.variant_name" class="variant">{{ item.variant_name }}</span>
              <span v-if="item.store_name" class="store">
                <i class="fas fa-store me-1"></i>{{ item.store_name }}
              </span>
            </div>
            <div class="item-price">
              <span class="unit-price">${{ formatPrice(item.unit_price) }}</span>
              <span v-if="item.quantity > 1" class="total-price">
                × {{ item.quantity }} = ${{ formatPrice(item.total_price) }}
              </span>
            </div>
          </div>

          <div class="item-actions">
            <!-- Quantity Controls -->
            <div class="quantity-controls">
              <button 
                @click="updateQuantity(item, item.quantity - 1)"
                :disabled="item.quantity <= 1 || updating"
                class="btn btn-outline-secondary btn-sm"
              >
                <i class="fas fa-minus"></i>
              </button>
              <input 
                v-model.number="item.quantity"
                @change="updateQuantity(item, item.quantity)"
                :disabled="updating"
                type="number" 
                min="1" 
                max="100"
                class="form-control quantity-input"
              >
              <button 
                @click="updateQuantity(item, item.quantity + 1)"
                :disabled="updating"
                class="btn btn-outline-secondary btn-sm"
              >
                <i class="fas fa-plus"></i>
              </button>
            </div>

            <!-- Remove Button -->
            <button 
              @click="removeItem(item)"
              :disabled="updating"
              class="btn btn-outline-danger btn-sm mt-2"
            >
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Cart Summary -->
      <div class="cart-summary">
        <div class="summary-row">
          <span>Subtotal ({{ cartData.total_items }} items):</span>
          <span class="fw-bold">${{ formatPrice(cartData.subtotal) }}</span>
        </div>
        
        <div v-if="cartTotals.tax_amount > 0" class="summary-row">
          <span>Tax:</span>
          <span>${{ formatPrice(cartTotals.tax_amount) }}</span>
        </div>
        
        <div v-if="cartTotals.delivery_fee > 0" class="summary-row">
          <span>Delivery Fee:</span>
          <span>${{ formatPrice(cartTotals.delivery_fee) }}</span>
        </div>
        
        <div v-if="cartTotals.discount_amount > 0" class="summary-row text-success">
          <span>Discount:</span>
          <span>-${{ formatPrice(cartTotals.discount_amount) }}</span>
        </div>
        
        <hr>
        
        <div class="summary-row total-row">
          <span class="fw-bold">Total:</span>
          <span class="fw-bold text-primary fs-5">
            ${{ formatPrice(cartTotals.total_amount || cartData.subtotal) }}
          </span>
        </div>

        <!-- Action Buttons -->
        <div class="cart-actions mt-4">
          <a href="/store" class="btn btn-outline-primary me-2">
            <i class="fas fa-arrow-left me-2"></i>
            Continue Shopping
          </a>
          <button 
            @click="proceedToCheckout"
            :disabled="cartData.items.length === 0"
            class="btn btn-primary"
          >
            <i class="fas fa-credit-card me-2"></i>
            Proceed to Checkout
          </button>
        </div>
      </div>
    </div>

    <!-- Error Messages -->
    <div v-if="error" class="alert alert-danger mt-3">
      {{ error }}
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'

// Props
const props = defineProps({
  showHeader: {
    type: Boolean,
    default: true
  },
  showActions: {
    type: Boolean,
    default: true
  }
})

// Reactive data
const cartData = ref({
  items: [],
  total_items: 0,
  subtotal: 0,
  stores: {},
  admin_products: []
})

const cartTotals = ref({
  subtotal: 0,
  tax_amount: 0,
  delivery_fee: 0,
  discount_amount: 0,
  total_amount: 0
})

const loading = ref(false)
const updating = ref(false)
const error = ref('')

// Computed
const isEmpty = computed(() => cartData.value.items.length === 0)

// Methods
const fetchCart = async () => {
  loading.value = true
  error.value = ''
  
  try {
    const response = await axios.get('/cart/')
    
    if (response.data.status) {
      cartData.value = response.data.data
      await fetchCartTotals()
    } else {
      error.value = response.data.message || 'Failed to fetch cart'
    }
  } catch (err) {
    error.value = 'Failed to load cart'
    console.error('Cart fetch error:', err)
  } finally {
    loading.value = false
  }
}

const fetchCartTotals = async () => {
  try {
    const response = await axios.get('/api/cart/totals')
    
    if (response.data.status) {
      cartTotals.value = response.data.data
    }
  } catch (err) {
    console.error('Cart totals fetch error:', err)
  }
}

const updateQuantity = async (item, newQuantity) => {
  if (newQuantity < 1 || newQuantity > 100) return
  
  updating.value = true
  error.value = ''
  
  try {
    const response = await axios.put('/cart/update', {
      item_key: item.item_key || item.id,
      quantity: newQuantity
    })
    
    if (response.data.status) {
      await fetchCart()
      emitCartUpdate()
    } else {
      error.value = response.data.message || 'Failed to update cart'
    }
  } catch (err) {
    error.value = 'Failed to update quantity'
    console.error('Update quantity error:', err)
  } finally {
    updating.value = false
  }
}

const removeItem = async (item) => {
  if (!confirm('Are you sure you want to remove this item from your cart?')) {
    return
  }
  
  updating.value = true
  error.value = ''
  
  try {
    const response = await axios.delete('/cart/remove', {
      data: { item_key: item.item_key || item.id }
    })
    
    if (response.data.status) {
      await fetchCart()
      emitCartUpdate()
    } else {
      error.value = response.data.message || 'Failed to remove item'
    }
  } catch (err) {
    error.value = 'Failed to remove item'
    console.error('Remove item error:', err)
  } finally {
    updating.value = false
  }
}

const clearCart = async () => {
  if (!confirm('Are you sure you want to clear your entire cart?')) {
    return
  }
  
  updating.value = true
  error.value = ''
  
  try {
    const response = await axios.delete('/cart/clear')
    
    if (response.data.status) {
      await fetchCart()
      emitCartUpdate()
    } else {
      error.value = response.data.message || 'Failed to clear cart'
    }
  } catch (err) {
    error.value = 'Failed to clear cart'
    console.error('Clear cart error:', err)
  } finally {
    updating.value = false
  }
}

const proceedToCheckout = () => {
  if (cartData.value.items.length === 0) return
  
  // Check if user is authenticated
  const isAuthenticated = document.querySelector('meta[name="user-authenticated"]')?.content === 'true'
  
  if (isAuthenticated) {
    window.location.href = '/checkout'
  } else {
    // Redirect to login with return URL
    window.location.href = `/login?redirect=${encodeURIComponent('/checkout')}`
  }
}

const formatPrice = (price) => {
  return parseFloat(price || 0).toFixed(2)
}

const emitCartUpdate = () => {
  // Emit custom event for other components to listen
  window.dispatchEvent(new CustomEvent('cart-updated', {
    detail: {
      count: cartData.value.total_items,
      subtotal: cartData.value.subtotal
    }
  }))
}

// Lifecycle
onMounted(() => {
  fetchCart()
})

// Expose methods for parent components
defineExpose({
  fetchCart,
  updateQuantity,
  removeItem,
  clearCart
})
</script>

<style scoped>
.shopping-cart {
  max-width: 800px;
  margin: 0 auto;
}

.cart-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #e9ecef;
}

.cart-title {
  margin: 0;
  color: #495057;
}

.cart-item {
  display: flex;
  align-items: center;
  padding: 1.5rem;
  margin-bottom: 1rem;
  background: #fff;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.item-image {
  width: 80px;
  height: 80px;
  margin-right: 1rem;
  flex-shrink: 0;
}

.item-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 6px;
}

.item-details {
  flex: 1;
  margin-right: 1rem;
}

.item-name {
  margin: 0 0 0.5rem 0;
  font-weight: 600;
  color: #495057;
}

.item-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}

.item-meta span {
  font-size: 0.875rem;
  color: #6c757d;
  background: #f8f9fa;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
}

.item-price {
  font-weight: 600;
  color: #495057;
}

.total-price {
  color: #28a745;
  margin-left: 0.5rem;
}

.item-actions {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
}

.quantity-controls {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.quantity-input {
  width: 60px;
  text-align: center;
  padding: 0.25rem;
}

.cart-summary {
  background: #f8f9fa;
  padding: 1.5rem;
  border-radius: 8px;
  margin-top: 2rem;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}

.total-row {
  font-size: 1.1rem;
  margin-top: 1rem;
}

.cart-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.empty-cart {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 3rem 2rem;
}

@media (max-width: 768px) {
  .cart-item {
    flex-direction: column;
    text-align: center;
  }
  
  .item-image {
    margin-right: 0;
    margin-bottom: 1rem;
  }
  
  .item-details {
    margin-right: 0;
    margin-bottom: 1rem;
  }
  
  .cart-actions {
    flex-direction: column;
  }
  
  .cart-actions .btn {
    width: 100%;
  }
}
</style>
