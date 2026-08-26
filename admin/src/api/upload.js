import request from './request'

// 上传图片 form-data: file，返回 {url}
export function uploadImage(file) {
  const formData = new FormData()
  formData.append('file', file)
  return request.post('/admin/upload/image', formData, {
    headers: { 'Content-Type': 'multipart/form-data' }
  })
}
