import React, { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import { useQuery, useMutation, useQueryClient } from 'react-query';
import { toast } from 'react-hot-toast';
import api from '../services/api';

// Types
interface User {
  id: number;
  email: string;
  username: string;
  full_name: string;
  role: string;
  is_active: boolean;
  student_id?: string;
  department?: string;
  year_level?: number;
  courses_enrolled: number[];
}

interface AuthContextType {
  user: User | null;
  isAuthenticated: boolean;
  loading: boolean;
  login: (email: string, password: string) => Promise<void>;
  register: (userData: RegisterData) => Promise<void>;
  logout: () => void;
  updateUser: (userData: Partial<User>) => Promise<void>;
}

interface RegisterData {
  email: string;
  username: string;
  full_name: string;
  password: string;
  role?: string;
  student_id?: string;
  department?: string;
  year_level?: number;
}

// Création du contexte
const AuthContext = createContext<AuthContextType | undefined>(undefined);

// Hook personnalisé pour utiliser le contexte
export const useAuth = () => {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};

// Provider du contexte
interface AuthProviderProps {
  children: ReactNode;
}

export const AuthProvider: React.FC<AuthProviderProps> = ({ children }) => {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);
  const queryClient = useQueryClient();

  // Vérifier si l'utilisateur est connecté au chargement
  useEffect(() => {
    const token = localStorage.getItem('token');
    if (token) {
      api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    }
    setLoading(false);
  }, []);

  // Requête pour récupérer les informations de l'utilisateur
  const { data: userData, isLoading: userLoading } = useQuery(
    'user',
    async () => {
      const token = localStorage.getItem('token');
      if (!token) throw new Error('No token');
      
      const response = await api.get('/api/v1/auth/me');
      return response.data;
    },
    {
      enabled: !!localStorage.getItem('token'),
      retry: false,
      onError: () => {
        localStorage.removeItem('token');
        delete api.defaults.headers.common['Authorization'];
        setUser(null);
      },
      onSuccess: (data) => {
        setUser(data);
      },
    }
  );

  // Mutation pour la connexion
  const loginMutation = useMutation(
    async ({ email, password }: { email: string; password: string }) => {
      const response = await api.post('/api/v1/auth/login', {
        username: email, // FastAPI OAuth2 utilise 'username'
        password,
      });
      return response.data;
    },
    {
      onSuccess: (data) => {
        const { access_token, user_info } = data;
        localStorage.setItem('token', access_token);
        api.defaults.headers.common['Authorization'] = `Bearer ${access_token}`;
        setUser(user_info);
        toast.success('Connexion réussie !');
      },
      onError: (error: any) => {
        const message = error.response?.data?.detail || 'Erreur de connexion';
        toast.error(message);
        throw error;
      },
    }
  );

  // Mutation pour l'inscription
  const registerMutation = useMutation(
    async (userData: RegisterData) => {
      const response = await api.post('/api/v1/auth/register', userData);
      return response.data;
    },
    {
      onSuccess: () => {
        toast.success('Inscription réussie ! Vous pouvez maintenant vous connecter.');
      },
      onError: (error: any) => {
        const message = error.response?.data?.detail || 'Erreur d\'inscription';
        toast.error(message);
        throw error;
      },
    }
  );

  // Mutation pour la mise à jour du profil
  const updateUserMutation = useMutation(
    async (userData: Partial<User>) => {
      const response = await api.put(`/api/v1/users/${user?.id}`, userData);
      return response.data;
    },
    {
      onSuccess: (data) => {
        setUser(data);
        queryClient.invalidateQueries('user');
        toast.success('Profil mis à jour avec succès !');
      },
      onError: (error: any) => {
        const message = error.response?.data?.detail || 'Erreur de mise à jour';
        toast.error(message);
        throw error;
      },
    }
  );

  // Fonctions d'authentification
  const login = async (email: string, password: string) => {
    await loginMutation.mutateAsync({ email, password });
  };

  const register = async (userData: RegisterData) => {
    await registerMutation.mutateAsync(userData);
  };

  const logout = () => {
    localStorage.removeItem('token');
    delete api.defaults.headers.common['Authorization'];
    setUser(null);
    queryClient.clear();
    toast.success('Déconnexion réussie !');
  };

  const updateUser = async (userData: Partial<User>) => {
    await updateUserMutation.mutateAsync(userData);
  };

  // État de chargement global
  const isLoading = loading || userLoading;

  const value: AuthContextType = {
    user,
    isAuthenticated: !!user,
    loading: isLoading,
    login,
    register,
    logout,
    updateUser,
  };

  return (
    <AuthContext.Provider value={value}>
      {children}
    </AuthContext.Provider>
  );
};
