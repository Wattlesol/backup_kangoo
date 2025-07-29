<template>
  <div class="cart-icon-wrapper">
    <a 
      href="/cart" 
      class="cart-icon-link"
      :class="{ 'has-items': cartCount > 0 }"
    >
      <div class="cart-icon">
        <i class="fas fa-shopping-cart"></i>
        <span 
          v-if="cartCount > 0" 
          class="cart-badge"
          :class="{ 'animate-bounce': isAnimating }"
        >
          {{ cartCount > 99 ? '99+' : cartCount }}
        </span>
      </div>
      <span v-if="showText" class="cart-text">
        Cart
        <span v-if="cartCount > 0" class="cart-count-text">
          ({{ cartCount }})
        </span>
      </span>
    </a>

    <!-- Mini Cart Dropdown (Optional) -->
    <div 
      v-if="showDropdown && cartCount > 0" 
      class="cart-dropdown"
      :class="{ 'show': dropdownOpen }"
    >
      <div class="dropdown-header">
        <h6>Cart Items ({{ cartCount }})</h6>
        <button @click="toggleDropdown" class="btn-close"></button>
      </div>
      
      <div class="dropdown-body">
        <div 
          v-for="item in cartItems.slice(0, 3)" 
          :key="item.id || item.item_key"
          class="mini-cart-item"
        >
          <img 
            :src="item.product_image || '/images/default-product.png'" 
            :alt="item.product_name"
            class="item-image"
          >
          <div class="item-info">
            <div class="item-name">{{ item.product_name }}</div>
            <div class="item-price">
              {{ item.quantity }} × ${{ formatPrice(item.unit_price) }}
            </div>
          </div>
        </div>
        
        <div v-if="cartItems.length > 3" class="more-items">
          +{{ cartItems.length - 3 }} more items
        </div>
      </div>
      
      <div class="dropdown-footer">
        <div class="total">
          Total: ${{ formatPrice(cartSubtotal) }}
        </div>
        <div class="actions">
          <a href="/cart" class="btn btn-outline-primary btn-sm">View Cart</a>
          <a href="/checkout" class="btn btn-primary btn-sm">Checkout</a>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import axios from 'axios'

// Props
const props = defineProps({
  showText: {
    type: Boolean,
    default: false
  },
  showDropdown: {
    type: Boolean,
    default: false
  },
  size: {
    type: String,
    default: 'medium', // small, medium, large
    validator: (value) => ['small', 'medium', 'large'].includes(value)
  }
})

// Reactive data
const cartCount = ref(0)
const cartItems = ref([])
const cartSubtotal = ref(0)
const dropdownOpen = ref(false)
const isAnimating = ref(false)

// Methods
const fetchCartCount = async () => {
  try {
    const response = await axios.get('/cart/count')
    
    if (response.data.status) {
      const newCount = response.data.data.count
      
      // Animate if count increased
      if (newCount > cartCount.value) {
        animateBadge()
      }
      
      cartCount.value = newCount
    }
  } catch (err) {
    console.error('Failed to fetch cart count:', err)
  }
}

const fetchCartItems = async () => {
  if (!props.showDropdown) return
  
  try {
    const response = await axios.get('/cart/')
    
    if (response.data.status) {
      cartItems.value = response.data.data.items
      cartSubtotal.value = response.data.data.subtotal
    }
  } catch (err) {
    console.error('Failed to fetch cart items:', err)
  }
}

const toggleDropdown = () => {
  dropdownOpen.value = !dropdownOpen.value
  
  if (dropdownOpen.value) {
    fetchCartItems()
  }
}

const closeDropdown = () => {
  dropdownOpen.value = false
}

const animateBadge = () => {
  isAnimating.value = true
  setTimeout(() => {
    isAnimating.value = false
  }, 600)
}

const formatPrice = (price) => {
  return parseFloat(price || 0).toFixed(2)
}

const handleCartUpdate = (event) => {
  cartCount.value = event.detail.count
  cartSubtotal.value = event.detail.subtotal
  
  if (dropdownOpen.value) {
    fetchCartItems()
  }
  
  animateBadge()
}

const handleClickOutside = (event) => {
  if (!event.target.closest('.cart-icon-wrapper')) {
    closeDropdown()
  }
}

// Lifecycle
onMounted(() => {
  fetchCartCount()
  
  // Listen for cart updates
  window.addEventListener('cart-updated', handleCartUpdate)
  
  // Listen for clicks outside dropdown
  if (props.showDropdown) {
    document.addEventListener('click', handleClickOutside)
  }
})

onUnmounted(() => {
  window.removeEventListener('cart-updated', handleCartUpdate)
  
  if (props.showDropdown) {
    document.removeEventListener('click', handleClickOutside)
  }
})

// Expose methods
defineExpose({
  fetchCartCount,
  toggleDropdown,
  closeDropdown
})
</script>

<style scoped>
.cart-icon-wrapper {
  position: relative;
  display: inline-block;
}

.cart-icon-link {
  display: flex;
  align-items: center;
  text-decoration: none;
  color: #495057;
  transition: color 0.3s ease;
}

.cart-icon-link:hover {
  color: #007bff;
  text-decoration: none;
}

.cart-icon-link.has-items {
  color: #007bff;
}

.cart-icon {
  position: relative;
  font-size: 1.5rem;
  margin-right: 0.5rem;
}

.cart-badge {
  position: absolute;
  top: -8px;
  right: -8px;
  background: #dc3545;
  color: white;
  border-radius: 50%;
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 600;
  line-height: 1;
  min-width: 20px;
}

.cart-badge.animate-bounce {
  animation: bounce 0.6s ease-in-out;
}

@keyframes bounce {
  0%, 20%, 53%, 80%, 100% {
    transform: translate3d(0, 0, 0);
  }
  40%, 43% {
    transform: translate3d(0, -8px, 0);
  }
  70% {
    transform: translate3d(0, -4px, 0);
  }
  90% {
    transform: translate3d(0, -2px, 0);
  }
}

.cart-text {
  font-weight: 500;
}

.cart-count-text {
  font-size: 0.875rem;
  color: #6c757d;
}

/* Dropdown Styles */
.cart-dropdown {
  position: absolute;
  top: 100%;
  right: 0;
  width: 320px;
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  z-index: 1000;
  opacity: 0;
  visibility: hidden;
  transform: translateY(-10px);
  transition: all 0.3s ease;
}

.cart-dropdown.show {
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
}

.dropdown-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  border-bottom: 1px solid #e9ecef;
}

.dropdown-header h6 {
  margin: 0;
  font-weight: 600;
  color: #495057;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.2rem;
  cursor: pointer;
  color: #6c757d;
  padding: 0;
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-close:hover {
  color: #495057;
}

.btn-close::before {
  content: '×';
}

.dropdown-body {
  max-height: 300px;
  overflow-y: auto;
  padding: 0.5rem 0;
}

.mini-cart-item {
  display: flex;
  align-items: center;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #f8f9fa;
}

.mini-cart-item:last-child {
  border-bottom: none;
}

.item-image {
  width: 40px;
  height: 40px;
  object-fit: cover;
  border-radius: 4px;
  margin-right: 0.75rem;
}

.item-info {
  flex: 1;
}

.item-name {
  font-size: 0.875rem;
  font-weight: 500;
  color: #495057;
  margin-bottom: 0.25rem;
  line-height: 1.2;
}

.item-price {
  font-size: 0.75rem;
  color: #6c757d;
}

.more-items {
  text-align: center;
  padding: 0.5rem;
  font-size: 0.875rem;
  color: #6c757d;
  font-style: italic;
}

.dropdown-footer {
  padding: 1rem;
  border-top: 1px solid #e9ecef;
  background: #f8f9fa;
}

.total {
  font-weight: 600;
  color: #495057;
  margin-bottom: 0.75rem;
  text-align: center;
}

.actions {
  display: flex;
  gap: 0.5rem;
}

.actions .btn {
  flex: 1;
  font-size: 0.875rem;
}

/* Size Variants */
.cart-icon-wrapper.size-small .cart-icon {
  font-size: 1.2rem;
}

.cart-icon-wrapper.size-small .cart-badge {
  width: 16px;
  height: 16px;
  font-size: 0.65rem;
  top: -6px;
  right: -6px;
}

.cart-icon-wrapper.size-large .cart-icon {
  font-size: 2rem;
}

.cart-icon-wrapper.size-large .cart-badge {
  width: 24px;
  height: 24px;
  font-size: 0.85rem;
  top: -10px;
  right: -10px;
}

/* Mobile Responsive */
@media (max-width: 768px) {
  .cart-dropdown {
    width: 280px;
    right: -50px;
  }
  
  .cart-text {
    display: none;
  }
}
</style>
