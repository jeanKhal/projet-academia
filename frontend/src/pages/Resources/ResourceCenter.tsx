import React, { useState } from 'react';
import { useQuery } from 'react-query';
import { 
  DocumentTextIcon, 
  VideoCameraIcon, 
  CodeBracketIcon, 
  BookOpenIcon,
  DownloadIcon,
  EyeIcon,
  CalendarIcon,
  UserIcon
} from '@heroicons/react/24/outline';
import { api } from '../../services/api';

interface Resource {
  id: number;
  title: string;
  description: string;
  type: 'document' | 'video' | 'code' | 'book' | 'presentation' | 'dataset';
  category: string;
  file_size: string;
  upload_date: string;
  author: string;
  downloads: number;
  views: number;
  tags: string[];
  file_url?: string;
}

const ResourceCenter: React.FC = () => {
  const [selectedType, setSelectedType] = useState('all');
  const [selectedCategory, setSelectedCategory] = useState('all');
  const [searchTerm, setSearchTerm] = useState('');

  const { data: resources, isLoading, error } = useQuery<Resource[]>('resources', async () => {
    const response = await api.get('/api/v1/resources');
    return response.data;
  });

  const resourceTypes = [
    { value: 'all', label: 'Tous', icon: '📚' },
    { value: 'document', label: 'Documents', icon: '📄' },
    { value: 'video', label: 'Vidéos', icon: '🎥' },
    { value: 'code', label: 'Code', icon: '💻' },
    { value: 'book', label: 'Livres', icon: '📖' },
    { value: 'presentation', label: 'Présentations', icon: '📊' },
    { value: 'dataset', label: 'Datasets', icon: '📊' }
  ];

  const categories = [
    'all', 'embedded-systems', 'artificial-intelligence', 'machine-learning', 
    'deep-learning', 'software-engineering', 'mathematics', 'programming'
  ];

  const filteredResources = resources?.filter(resource => {
    const matchesSearch = resource.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         resource.description.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         resource.tags.some(tag => tag.toLowerCase().includes(searchTerm.toLowerCase()));
    const matchesType = selectedType === 'all' || resource.type === selectedType;
    const matchesCategory = selectedCategory === 'all' || resource.category === selectedCategory;
    
    return matchesSearch && matchesType && matchesCategory;
  });

  const getTypeIcon = (type: string) => {
    switch (type) {
      case 'document': return <DocumentTextIcon className="h-5 w-5" />;
      case 'video': return <VideoCameraIcon className="h-5 w-5" />;
      case 'code': return <CodeBracketIcon className="h-5 w-5" />;
      case 'book': return <BookOpenIcon className="h-5 w-5" />;
      case 'presentation': return <DocumentTextIcon className="h-5 w-5" />;
      case 'dataset': return <DocumentTextIcon className="h-5 w-5" />;
      default: return <DocumentTextIcon className="h-5 w-5" />;
    }
  };

  const getTypeColor = (type: string) => {
    switch (type) {
      case 'document': return 'bg-blue-100 text-blue-800';
      case 'video': return 'bg-red-100 text-red-800';
      case 'code': return 'bg-green-100 text-green-800';
      case 'book': return 'bg-purple-100 text-purple-800';
      case 'presentation': return 'bg-yellow-100 text-yellow-800';
      case 'dataset': return 'bg-indigo-100 text-indigo-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-center">
          <div className="text-red-600 text-xl mb-4">Erreur lors du chargement des ressources</div>
          <button 
            onClick={() => window.location.reload()} 
            className="btn-primary"
          >
            Réessayer
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="container mx-auto px-4 py-8">
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-gray-900 mb-2">Centre de Ressources</h1>
        <p className="text-gray-600">Accédez à une vaste collection de ressources éducatives</p>
      </div>

      {/* Statistiques */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div className="bg-white rounded-lg shadow-sm border p-6">
          <div className="flex items-center">
            <div className="p-2 bg-blue-100 rounded-lg">
              <DocumentTextIcon className="h-6 w-6 text-blue-600" />
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Total Ressources</p>
              <p className="text-2xl font-bold text-gray-900">{resources?.length || 0}</p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow-sm border p-6">
          <div className="flex items-center">
            <div className="p-2 bg-green-100 rounded-lg">
              <DownloadIcon className="h-6 w-6 text-green-600" />
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Téléchargements</p>
              <p className="text-2xl font-bold text-gray-900">
                {resources?.reduce((sum, r) => sum + r.downloads, 0) || 0}
              </p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow-sm border p-6">
          <div className="flex items-center">
            <div className="p-2 bg-purple-100 rounded-lg">
              <EyeIcon className="h-6 w-6 text-purple-600" />
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Vues</p>
              <p className="text-2xl font-bold text-gray-900">
                {resources?.reduce((sum, r) => sum + r.views, 0) || 0}
              </p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow-sm border p-6">
          <div className="flex items-center">
            <div className="p-2 bg-yellow-100 rounded-lg">
              <UserIcon className="h-6 w-6 text-yellow-600" />
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Auteurs</p>
              <p className="text-2xl font-bold text-gray-900">
                {new Set(resources?.map(r => r.author) || []).size}
              </p>
            </div>
          </div>
        </div>
      </div>

      {/* Filtres */}
      <div className="bg-white rounded-lg shadow-sm border p-6 mb-8">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          {/* Recherche */}
          <div className="md:col-span-2">
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Rechercher une ressource
            </label>
            <input
              type="text"
              placeholder="Rechercher par titre, description ou tags..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>

          {/* Type de ressource */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Type
            </label>
            <select
              value={selectedType}
              onChange={(e) => setSelectedType(e.target.value)}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              {resourceTypes.map(type => (
                <option key={type.value} value={type.value}>
                  {type.icon} {type.label}
                </option>
              ))}
            </select>
          </div>

          {/* Catégorie */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Catégorie
            </label>
            <select
              value={selectedCategory}
              onChange={(e) => setSelectedCategory(e.target.value)}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="all">Toutes les catégories</option>
              <option value="embedded-systems">Systèmes Embarqués</option>
              <option value="artificial-intelligence">Intelligence Artificielle</option>
              <option value="machine-learning">Machine Learning</option>
              <option value="deep-learning">Deep Learning</option>
              <option value="software-engineering">Génie Logiciel</option>
              <option value="mathematics">Mathématiques</option>
              <option value="programming">Programmation</option>
            </select>
          </div>
        </div>
      </div>

      {/* Résultats */}
      <div className="mb-4">
        <p className="text-gray-600">
          {filteredResources?.length || 0} ressource{filteredResources?.length !== 1 ? 's' : ''} trouvée{filteredResources?.length !== 1 ? 's' : ''}
        </p>
      </div>

      {/* Liste des ressources */}
      <div className="space-y-4">
        {filteredResources?.map((resource) => (
          <div key={resource.id} className="bg-white rounded-lg shadow-sm border p-6 hover:shadow-md transition-shadow duration-200">
            <div className="flex items-start justify-between">
              <div className="flex items-start space-x-4 flex-1">
                {/* Icône du type */}
                <div className={`p-3 rounded-lg ${getTypeColor(resource.type)}`}>
                  {getTypeIcon(resource.type)}
                </div>

                {/* Contenu */}
                <div className="flex-1">
                  <div className="flex items-center space-x-2 mb-2">
                    <h3 className="text-lg font-semibold text-gray-900">{resource.title}</h3>
                    <span className={`px-2 py-1 rounded-full text-xs font-medium ${getTypeColor(resource.type)}`}>
                      {resource.type === 'document' ? 'Document' :
                       resource.type === 'video' ? 'Vidéo' :
                       resource.type === 'code' ? 'Code' :
                       resource.type === 'book' ? 'Livre' :
                       resource.type === 'presentation' ? 'Présentation' : 'Dataset'}
                    </span>
                  </div>

                  <p className="text-gray-600 mb-3">{resource.description}</p>

                  {/* Tags */}
                  <div className="flex flex-wrap gap-2 mb-3">
                    {resource.tags.map((tag, index) => (
                      <span key={index} className="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded-full">
                        #{tag}
                      </span>
                    ))}
                  </div>

                  {/* Métadonnées */}
                  <div className="flex items-center space-x-6 text-sm text-gray-500">
                    <div className="flex items-center">
                      <UserIcon className="h-4 w-4 mr-1" />
                      {resource.author}
                    </div>
                    <div className="flex items-center">
                      <CalendarIcon className="h-4 w-4 mr-1" />
                      {formatDate(resource.upload_date)}
                    </div>
                    <div className="flex items-center">
                      <DownloadIcon className="h-4 w-4 mr-1" />
                      {resource.downloads} téléchargements
                    </div>
                    <div className="flex items-center">
                      <EyeIcon className="h-4 w-4 mr-1" />
                      {resource.views} vues
                    </div>
                    <div className="text-gray-400">
                      {resource.file_size}
                    </div>
                  </div>
                </div>
              </div>

              {/* Actions */}
              <div className="flex space-x-2 ml-4">
                <button className="btn-primary text-sm py-2 px-4">
                  <DownloadIcon className="h-4 w-4 mr-1" />
                  Télécharger
                </button>
                <button className="btn-secondary text-sm py-2 px-4">
                  <EyeIcon className="h-4 w-4 mr-1" />
                  Voir
                </button>
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* Message si aucune ressource trouvée */}
      {filteredResources?.length === 0 && (
        <div className="text-center py-12">
          <div className="text-gray-400 text-6xl mb-4">📚</div>
          <h3 className="text-xl font-semibold text-gray-900 mb-2">Aucune ressource trouvée</h3>
          <p className="text-gray-600">Essayez de modifier vos critères de recherche</p>
        </div>
      )}
    </div>
  );
};

export default ResourceCenter;
