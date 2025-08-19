<template>

    <section ref="categorySection">
        <div class="row row-cols-xl-4 row-cols-md-3 row-cols-sm-2 row-cols-1 justify-content-center mt-5" v-if="categoryDetails.length > 0">
            <div v-for="category in categoryDetails" :key="category.id" class="col">
                <category-card :category_id="category.id" :title="category.name" :description="category.description" :image="category.category_image"/>
            </div>
        </div>
        <div class="row row-cols-xl-4 row-cols-md-3 row-cols-sm-2 row-cols-1 justify-content-center mt-5 " >

           <span v-if="categoryDetails.length ==0 && isLoading==0"> Data Not Available </span>

            <!-- <CategoryShimmer  v-for="item in 8" :key="item"></CategoryShimmer> -->
          
        </div>
    </section>

</template>
<script setup>
import { onMounted,ref, defineProps, watchEffect } from 'vue';
import CategoryCard from '../components/CategoryCard.vue';
import CategoryShimmer  from '../shimmer/CategoryShimmer.vue'
import {useSection} from '../store/index'
const store = useSection()
const props = defineProps({ categoryIds: { type: Array, default: () => [] } })
const categoryDetails = ref([]);
const isLoading=ref(1);

// fetch only the categories referenced by landing settings via the store (deduped)
const loadByIds = async (ids) => {
  if (!ids || ids.length === 0) { categoryDetails.value = []; isLoading.value = 0; return; }
  try {
    await store.get_categries_list({ per_page: 'all', ids: ids.join(',') });
    const cats = store.categries_list_data?.data || [];
    categoryDetails.value = cats.map(category => ({
      id: category.id,
      name: category.name,
      description: category.description,
      category_image: category.category_image,
    }));
  } catch (e) { console.error('Category load failed', e); }
  finally { isLoading.value = 0; }
}

onMounted(async () => {
  await loadByIds(props.categoryIds);
});

watchEffect(() => {
  if (Array.isArray(props.categoryIds)) {
    loadByIds(props.categoryIds);
  }
});
</script>
